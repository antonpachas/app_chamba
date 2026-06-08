const defaultPlatform = {
    provider_public_profile: true,
    provider_show_contact:   true,
    search_grid_columns_sm:  1,
    search_grid_columns_md:  2,
};

export const platform = {
    ...defaultPlatform,
    ...(typeof window !== 'undefined' && window.CHAMBA_PLATFORM ? window.CHAMBA_PLATFORM : {}),
};

export function providerPublicProfileEnabled() {
    return !!platform.provider_public_profile;
}

// Escrow y suscripciones están desactivados en BuscaPE
export function escrowEnabled() { return false; }
export function subscriptionsEnabled() { return false; }
