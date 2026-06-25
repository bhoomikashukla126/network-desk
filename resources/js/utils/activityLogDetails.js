const MONEY_KEYS = new Set(['amount', 'total_amount', 'subtotal', 'tax_amount', 'price', 'purchase_price']);

function humanizeKey(key) {
    return String(key)
        .replace(/^custom:/, '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatMoney(value) {
    const amount = Number(value);

    if (!Number.isFinite(amount)) {
        return null;
    }

    return new Intl.NumberFormat('en-IN', {
        style: 'currency',
        currency: 'INR',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(amount);
}

export function formatActivityValue(key, value) {
    if (value === null || value === undefined || value === '') {
        return null;
    }

    const fieldKey = key.includes(':') ? key.split(':').pop() : key;

    if (MONEY_KEYS.has(fieldKey)) {
        return formatMoney(value);
    }

    if (typeof value === 'boolean') {
        return value ? 'Yes' : 'No';
    }

    if (Array.isArray(value)) {
        if (value.length === 0) {
            return null;
        }

        return value
            .map((item) => {
                if (item === null || item === undefined || item === '') {
                    return null;
                }

                if (typeof item === 'object') {
                    const nested = Object.entries(item)
                        .map(([nestedKey, nestedValue]) => {
                            const formatted = formatActivityValue(nestedKey, nestedValue);

                            return formatted == null ? null : `${humanizeKey(nestedKey)}: ${formatted}`;
                        })
                        .filter(Boolean)
                        .join(', ');

                    return nested || null;
                }

                return String(item);
            })
            .filter(Boolean)
            .join(' · ');
    }

    if (typeof value === 'object') {
        const parts = Object.entries(value)
            .map(([nestedKey, nestedValue]) => {
                const formatted = formatActivityValue(nestedKey, nestedValue);

                return formatted == null ? null : `${humanizeKey(nestedKey)}: ${formatted}`;
            })
            .filter(Boolean);

        return parts.length > 0 ? parts.join(' · ') : null;
    }

    return String(value);
}

function resolveFieldLabel(key, entry, translate) {
    if (entry?.label) {
        return entry.label;
    }

    const fieldKey = key.startsWith('custom:') ? key.slice(7) : key;
    const i18nKey = `activity.fields.${fieldKey}`;
    const translated = translate(i18nKey);

    return translated !== i18nKey ? translated : humanizeKey(fieldKey);
}

function formatEmptyValue(translate) {
    const label = translate('activity.emptyValue');

    return label !== 'activity.emptyValue' ? label : 'Empty';
}

function formatChangeValue(key, value, translate) {
    const formatted = formatActivityValue(key, value);

    return formatted == null ? formatEmptyValue(translate) : formatted;
}

function buildStructuredDetailLines(log, metadata, translate) {
    const lines = [];
    const operation = metadata.operation;

    if (log.subject_type) {
        const subjectId = log.subject_id != null && log.subject_id !== '' ? `#${log.subject_id}` : '';

        lines.push({
            key: 'subject',
            label: translate('activity.fields.subject'),
            value: `${humanizeKey(String(log.subject_type).replace(/\./g, ' '))} ${subjectId}`.trim(),
        });
    }

    const operationKey = `activity.operation.${operation}`;
    const operationLabel = translate(operationKey);

    if (operationLabel !== operationKey) {
        lines.push({
            key: 'operation',
            label: translate('activity.fields.operation'),
            value: operationLabel,
        });
    }

    Object.entries(metadata.fields ?? {}).forEach(([key, entry]) => {
        const label = resolveFieldLabel(key, entry, translate);

        if (operation === 'update') {
            const from = formatChangeValue(key, entry.from, translate);
            const to = formatChangeValue(key, entry.to, translate);

            lines.push({
                key,
                label,
                value: `${from} → ${to}`,
            });

            return;
        }

        const formatted = formatActivityValue(key, entry.value);

        if (formatted == null) {
            return;
        }

        lines.push({
            key,
            label,
            value: formatted,
        });
    });

    return lines;
}

function buildLegacyDetailLines(log, metadata, translate) {
    const lines = [];

    if (log.subject_type) {
        const subjectId = log.subject_id != null && log.subject_id !== '' ? `#${log.subject_id}` : '';

        lines.push({
            key: 'subject',
            label: translate('activity.fields.subject'),
            value: `${humanizeKey(String(log.subject_type).replace(/\./g, ' '))} ${subjectId}`.trim(),
        });
    }

    Object.entries(metadata).forEach(([key, value]) => {
        if (key === 'operation' || key === 'fields') {
            return;
        }

        const formatted = formatActivityValue(key, value);

        if (formatted == null) {
            return;
        }

        const i18nKey = `activity.fields.${key}`;
        const label = translate(i18nKey);

        lines.push({
            key,
            label: label !== i18nKey ? label : humanizeKey(key),
            value: formatted,
        });
    });

    return lines;
}

export function buildActivityDetailLines(log, translate) {
    const metadata = log.metadata ?? {};

    if (metadata.operation && metadata.fields) {
        return buildStructuredDetailLines(log, metadata, translate);
    }

    return buildLegacyDetailLines(log, metadata, translate);
}
