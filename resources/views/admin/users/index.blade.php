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
                <form method="GET" action="{{ route('admin.users') }}" class="flex gap-2">
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Rechercher par nom ou email..."
                        class="w-full max-w-sm rounded-lg border-2 border-slate-200 px-3.5 py-2 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
                    >
                    <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">
                        Rechercher
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('admin.users') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-secondary dark:border-slate-700 dark:text-light">
                            Réinitialiser
                        </a>
                    @endif
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                                <th class="px-4 py-3 font-medium">Nom</th>
                                <th class="px-4 py-3 font-medium">Email</th>
                                <th class="px-4 py-3 font-medium">Solde</th>
                                <th class="px-4 py-3 font-medium">Statut</th>
                                <th class="px-4 py-3 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($users as $user)
                                <tr x-data="{ crediting: false }">
                                    <td class="px-4 py-3 text-secondary dark:text-light">
                                        {{ $user->name }}
                                        @if ($user->is_admin)
                                            <span class="ml-1 rounded-full bg-accent/10 px-2 py-0.5 text-[10px] font-semibold text-accent">ADMIN</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-secondary/80 dark:text-light/80">{{ $user->email }}</td>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ number_format($user->balance, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        @if ($user->is_suspended)
                                            <span class="rounded-full bg-error/10 px-2.5 py-0.5 text-xs font-medium text-error">Suspendu</span>
                                        @else
                                            <span class="rounded-full bg-success/10 px-2.5 py-0.5 text-xs font-medium text-success">Actif</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    onclick="return confirm('{{ $user->is_suspended ? 'Réactiver' : 'Suspendre' }} ce compte ?')"
                                                    class="rounded-full border px-3 py-1 text-xs font-medium {{ $user->is_suspended ? 'border-success/30 text-success hover:bg-success/5' : 'border-error/30 text-error hover:bg-error/5' }}"
                                                >
                                                    {{ $user->is_suspended ? 'Réactiver' : 'Suspendre' }}
                                                </button>
                                            </form>

                                            <button
                                                type="button"
                                                @click="crediting = !crediting"
                                                class="rounded-full border border-primary/30 px-3 py-1 text-xs font-medium text-primary hover:bg-primary/5"
                                            >
                                                Créditer
                                            </button>
                                        </div>

                                        <div x-show="crediting" x-cloak class="mt-2">
                                            <form method="POST" action="{{ route('admin.users.credit', $user) }}" class="flex flex-wrap items-center gap-2">
                                                @csrf
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="amount_fcfa"
                                                    placeholder="Montant (± FCFA)"
                                                    required
                                                    class="w-32 rounded-lg border-2 border-slate-200 px-2.5 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-light"
                                                >
                                                <input
                                                    type="text"
                                                    name="reason"
                                                    placeholder="Raison (optionnel)"
                                                    class="w-40 rounded-lg border-2 border-slate-200 px-2.5 py-1.5 text-xs dark:border-slate-700 dark:bg-slate-800 dark:text-light"
                                                >
                                                <button type="submit" class="rounded-full bg-primary px-3 py-1.5 text-xs font-semibold text-white">
                                                    Valider
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-10 text-center text-sm text-secondary/60 dark:text-light/60">
                                        Aucun utilisateur trouvé.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $users->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
