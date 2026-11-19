<?php

namespace App\Http\Controllers;

use App\Support\Images\MediaSizes;
use App\Support\Images\ProtectedImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Shows a protected image (AKAR-51, see ProtectedImages), without a session (no queries). An admin's signed link gets
 * any file. A visitor gets only the website's
 * sizes, through a valid signed link, and only inside a page of this site: an image opened on its own (a new tab, a
 * pasted address) or shown by another site gets nothing. A link that ran out while one of our pages still asks for it
 * (a photo shown long after the page opened) is answered with a fresh one.
 */
class ProtectedImageController extends Controller
{
    /** What the browser loads the file as when it's opened on its own, not shown in a page. */
    private const NOT_IN_A_PAGE = ['document', 'iframe', 'frame', 'embed', 'object'];

    public function __invoke(Request $request, string $path): BinaryFileResponse|RedirectResponse
    {
        abort_if(str_contains($path, '..') || str_contains($path, "\0"), 404);

        // An admin's link (signed with "a", see ProtectedImages::url): any file, while it works. No session is read.
        $admin = $request->query('a') === '1' && URL::hasValidRelativeSignature($request);
        abort_if($request->query('a') !== null && ! $admin, 403);

        if (! $admin) {
            abort_if(in_array($request->header('Sec-Fetch-Dest'), self::NOT_IN_A_PAGE, true), 403);
            abort_if($request->header('Sec-Fetch-Site') === 'cross-site', 403);
            abort_unless(ProtectedImages::isForWebsite($path), 404);

            if (! URL::hasCorrectSignature($request, absolute: false)) {
                abort(403);
            }
            if (! URL::signatureHasNotExpired($request)) {
                // Still asked for by one of our pages: a fresh link (the browser follows it).
                abort_unless($request->header('Sec-Fetch-Site') === 'same-origin', 403);

                return redirect(ProtectedImages::url($path));
            }
        }

        $disk = Storage::disk(ProtectedImages::DISK);
        abort_unless($disk->exists($path), 404);

        $seconds = $admin ? 300 : max(0, min((int) $request->query('expires') - now()->getTimestamp(), ProtectedImages::LIFETIME * 2));

        // With the watermark on, the photos' website sizes are sent with it (MediaSizes).
        $response = response()->file(MediaSizes::fileToSend($path), [
            'X-Content-Type-Options' => 'nosniff',
            // Other sites can't show it.
            'Cross-Origin-Resource-Policy' => 'same-origin',
        ]);

        // Kept by this browser only (never a shared cache: a proxy, a CDN), for as long as the link works. Set after
        // the response is made: a file response is public by default and would override a header given above.
        return $response->setPrivate()->setMaxAge($seconds);
    }
}
