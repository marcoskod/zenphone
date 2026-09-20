<p class="mb-4 text-sm text-secondary/70 dark:text-light/70">
    Une question, un problème avec une commande, une suggestion ? Écrivez-nous, nous répondons généralement sous 24 heures.
</p>

<a href="mailto:support@zenphone.space" class="mb-4 flex items-center gap-2 rounded-lg border border-slate-200 px-3.5 py-2.5 text-sm font-semibold text-primary transition hover:bg-slate-50 dark:border-slate-700 dark:text-accent dark:hover:bg-slate-800">
    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
    support@zenphone.space
</a>

<template x-if="contactSent">
    <div class="rounded-lg border border-success/30 bg-success/5 p-4 text-center text-sm text-success">
        <i class="fa-solid fa-circle-check"></i>
        Votre message a bien été envoyé. Nous vous répondrons rapidement.
    </div>
</template>

<form @submit.prevent="sendContact()" x-show="!contactSent" class="space-y-3">
    <div>
        <label class="mb-1 block text-xs font-medium text-secondary dark:text-light">Nom</label>
        <input
            type="text"
            x-model="contactName"
            required
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-secondary dark:text-light">Email</label>
        <input
            type="email"
            x-model="contactEmail"
            required
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-secondary dark:text-light">Sujet (optionnel)</label>
        <input
            type="text"
            x-model="contactSubject"
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    <div>
        <label class="mb-1 block text-xs font-medium text-secondary dark:text-light">Message</label>
        <textarea
            x-model="contactMessage"
            required
            rows="4"
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        ></textarea>
    </div>
    <button
        type="submit"
        :disabled="contactSending"
        class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50"
    >
        <i class="fa-solid fa-paper-plane"></i>
        <span x-show="!contactSending">Envoyer</span>
        <span x-show="contactSending" x-cloak>Envoi...</span>
    </button>
</form>
