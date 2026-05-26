const defaultFeatures = { escrow: false, subscriptions: true };
const defaultPricing = {
    provider_pro_price: 29,
    client_premium_price: 9,
    free_contacts_per_month: 3,
    currency: 'PEN',
};

export const features = {
    ...defaultFeatures,
    ...(typeof window !== 'undefined' && window.CHAMBA_FEATURES ? window.CHAMBA_FEATURES : {}),
};

export const pricing = {
    ...defaultPricing,
    ...(typeof window !== 'undefined' && window.CHAMBA_PRICING ? window.CHAMBA_PRICING : {}),
};

export function escrowEnabled() {
    return !!features.escrow;
}

export function subscriptionsEnabled() {
    return !!features.subscriptions;
}
