<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Administration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('admin.partials.nav')

            @if (session('status'))
                <div class="rounded-lg border border-success/30 bg-success/5 p-3.5 text-sm text-success">
                    {{ session('status') }}
                </div>
            @endif

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('admin.support') }}" class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('admin.support') }}" class="rounded-full px-3 py-1.5 text-xs font-medium {{ $status === '' ? 'bg-primary text-white' : 'border border-slate-300 text-secondary dark:border-slate-700 dark:text-light' }}">Tous</a>
                    <a href="{{ route('admin.support', ['status' => 'open']) }}" class="rounded-full px-3 py-1.5 text-xs font-medium {{ $status === 'open' ? 'bg-primary text-white' : 'border border-slate-300 text-secondary dark:border-slate-700 dark:text-light' }}">Ouverts</a>
                    <a href="{{ route('admin.support', ['status' => 'in_progress']) }}" class="rounded-full px-3 py-1.5 text-xs font-medium {{ $status === 'in_progress' ? 'bg-primary text-white' : 'border border-slate-300 text-secondary dark:border-slate-700 dark:text-light' }}">En cours</a>
                    <a href="{{ route('admin.support', ['status' => 'closed']) }}" class="rounded-full px-3 py-1.5 text-xs font-medium {{ $status === 'closed' ? 'bg-primary text-white' : 'border border-slate-300 text-secondary dark:border-slate-700 dark:text-light' }}">Fermés</a>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($tickets as $ticket)
                        <div class="p-4">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-secondary dark:text-light">
                                        {{ $ticket->subject ?: '(Sans sujet)' }}
                                    </p>
                                    <p class="text-xs text-secondary/60 dark:text-light/60">
                                        {{ $ticket->name }} &lt;{{ $ticket->email }}&gt; · {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    </p>
                                </div>

                                <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="flex items-center gap-2">
                                    @csrf
                                    <select name="status" class="rounded-lg border-2 border-slate-200 px-2.5 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                                        <option value="open" @selected($ticket->status === 'open')>Ouvert</option>
                                        <option value="in_progress" @selected($ticket->status === 'in_progress')>En cours</option>
                                        <option value="closed" @selected($ticket->status === 'closed')>Fermé</option>
                                    </select>
                                    <button type="submit" class="rounded-full bg-primary px-3 py-1.5 text-xs font-semibold text-white">
                                        Mettre à jour
                                    </button>
                                </form>
                            </div>

                            <p class="mt-2 text-sm text-secondary/80 dark:text-light/80">{{ $ticket->message }}</p>
                        </div>
                    @empty
                        <p class="p-8 text-center text-sm text-secondary/60 dark:text-light/60">Aucun ticket trouvé.</p>
                    @endforelse
                </div>

                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $tickets->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
