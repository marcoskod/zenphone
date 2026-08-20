{{-- Circumference for r=54: 2 * pi * 54 ≈ 339.292 --}}
<div class="flex flex-col items-center gap-2">
    <div class="relative flex h-32 w-32 items-center justify-center">
        <svg class="h-32 w-32 -rotate-90" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="54" stroke-width="10" fill="none" class="stroke-slate-200 dark:stroke-slate-700" />
            <circle
                cx="60"
                cy="60"
                r="54"
                stroke-width="10"
                fill="none"
                stroke-linecap="round"
                stroke-dasharray="339.292"
                :stroke-dashoffset="339.292 - (339.292 * progressPercent / 100)"
                :class="secondsRemaining > 0 && secondsRemaining <= 30 ? 'stroke-error' : 'stroke-primary'"
                class="transition-all duration-1000 ease-linear"
            />
        </svg>
        <div class="absolute flex flex-col items-center">
            <span class="text-2xl font-bold text-secondary dark:text-light" x-text="`${minutes}:${seconds}`"></span>
            <span class="text-xs text-secondary/50 dark:text-light/50">restant</span>
        </div>
    </div>
</div>
