{{-- Put the brand first in the browser tab: "Users - Akar Town" becomes "Akar Town | Users". --}}
<div
    x-data="{ brand: @js(filament()->getBrandName()) }"
    x-init="
        const suffix = ' - ' + brand;
        if (document.title.endsWith(suffix)) {
            document.title = brand + ' | ' + document.title.slice(0, -suffix.length);
        }
    "
    class="hidden"
></div>
