const defaultFeatures = { escrow: false, subscriptions: true };
const defaultPlatform = {
    provider_public_profile: true,
    provider_show_contact: true,
    search_grid_columns_sm: 1,
    search_grid_columns_md: 2,
};
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

export const platform = {
    ...defaultPlatform,
    ...(typeof window !== 'undefined' && window.CHAMBA_PLATFORM ? window.CHAMBA_PLATFORM : {}),
};

export function escrowEnabled() {
    return !!features.escrow;
}

export function subscriptionsEnabled() {
    return !!features.subscriptions;
}

export function providerPublicProfileEnabled() {
    return !!platform.provider_public_profile;
}
