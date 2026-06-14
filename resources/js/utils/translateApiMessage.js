import { i18n } from '../i18n';

const QUOTA_MESSAGE_KEYS = {
    'Workspace API request limit reached. Contact your platform admin to increase the limit.': 'errors.quota.apiRequestLimit',
    'Workspace storage limit reached. Contact your platform admin to increase the limit.': 'errors.quota.storageLimit',
};

export function translateApiMessage(message) {
    if (typeof message !== 'string') {
        return message;
    }

    const key = QUOTA_MESSAGE_KEYS[message];

    if (!key) {
        return message;
    }

    const translated = i18n.global.t(key);

    return translated !== key ? translated : message;
}
