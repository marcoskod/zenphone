@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-xl border-slate-300 bg-white text-secondary shadow-sm transition focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light dark:placeholder:text-light/30']) }}>
