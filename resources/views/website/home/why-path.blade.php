{{-- The design's dotted path joining the four stages; it is drawn left to right when it scrolls into view. --}}
<svg
  class="pointer-events-none absolute top-1/2 left-0 h-[260px] w-full -translate-y-1/2 overflow-visible max-[900px]:hidden"
  viewBox="0 0 1000 260"
  preserveAspectRatio="none"
  aria-hidden="true"
>
  <defs>
    <clipPath id="whyPathClip">
      <!-- width starts at 0; JS animates it to 790 to reveal path left→right -->
      <rect x-ref="clip" x="115" y="0" width="0" height="260" />
    </clipPath>
  </defs>
  <path
    class="dark:stroke-[rgba(200,16,46,0.6)]"
    d="M 125,200 C 200,200 200,60 375,60 C 500,60 500,200 625,200 C 750,200 750,60 875,60"
    fill="none"
    stroke="rgba(200,16,46,0.6)"
    stroke-width="1.5"
    stroke-dasharray="6 8"
    stroke-linecap="round"
    clip-path="url(#whyPathClip)"
  />
  <!-- Stage dots on path -->
  <circle
    class="dark:fill-[rgba(200,16,46,0.9)]"
    cx="125"
    cy="200"
    r="5"
    fill="var(--color-brand)"
  />
  <circle
    class="dark:fill-[rgba(200,16,46,0.9)]"
    cx="375"
    cy="60"
    r="5"
    fill="var(--color-brand)"
  />
  <circle
    class="dark:fill-[rgba(200,16,46,0.9)]"
    cx="625"
    cy="200"
    r="5"
    fill="var(--color-brand)"
  />
  <circle
    class="dark:fill-[rgba(200,16,46,0.9)]"
    cx="875"
    cy="60"
    r="5"
    fill="var(--color-brand)"
  />
  <!-- Outer pulse rings -->
  <circle
    class="dark:fill-none dark:stroke-[rgba(200,16,46,0.5)]"
    cx="125"
    cy="200"
    r="10"
    fill="none"
    stroke="var(--color-brand)"
    stroke-width="1.5"
  />
  <circle
    class="dark:fill-none dark:stroke-[rgba(200,16,46,0.5)]"
    cx="375"
    cy="60"
    r="10"
    fill="none"
    stroke="var(--color-brand)"
    stroke-width="1.5"
  />
  <circle
    class="dark:fill-none dark:stroke-[rgba(200,16,46,0.5)]"
    cx="625"
    cy="200"
    r="10"
    fill="none"
    stroke="var(--color-brand)"
    stroke-width="1.5"
  />
  <circle
    class="dark:fill-none dark:stroke-[rgba(200,16,46,0.5)]"
    cx="875"
    cy="60"
    r="10"
    fill="none"
    stroke="var(--color-brand)"
    stroke-width="1.5"
  />
</svg>
