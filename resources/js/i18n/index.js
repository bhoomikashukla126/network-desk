import { createI18n } from 'vue-i18n';
import en from '../locales/en.json';
import hi from '../locales/hi.json';

const supportedLocales = ['en', 'hi'];

export const i18n = createI18n({
    legacy: false,
    locale: 'en',
    fallbackLocale: 'en',
    messages: { en, hi },
});

export function setLocaleFromWorkspace(language) {
    const locale = supportedLocales.includes(language) ? language : 'en';
    i18n.global.locale.value = locale;
}

export function currentLocale() {
    return i18n.global.locale.value;
}
