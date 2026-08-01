/** Minimal helpers for WebAuthn JSON ↔ binary (lbuchs/webauthn server). */

function b64urlToBuffer(value: string): ArrayBuffer {
    const padded = value.replace(/-/g, '+').replace(/_/g, '/');
    const pad = padded.length % 4 === 0 ? '' : '='.repeat(4 - (padded.length % 4));
    const binary = atob(padded + pad);
    const bytes = new Uint8Array(binary.length);
    for (let i = 0; i < binary.length; i++) {
        bytes[i] = binary.charCodeAt(i);
    }
    return bytes.buffer;
}

function bufferToB64url(buffer: ArrayBuffer): string {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    bytes.forEach((b) => {
        binary += String.fromCharCode(b);
    });
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

type ServerCreationOptions = {
    publicKey: {
        challenge: string;
        rp: PublicKeyCredentialRpEntity;
        user: {
            id: string;
            name: string;
            displayName: string;
        };
        pubKeyCredParams: PublicKeyCredentialParameters[];
        timeout?: number;
        attestation?: AttestationConveyancePreference;
        authenticatorSelection?: AuthenticatorSelectionCriteria;
        excludeCredentials?: Array<{ type: PublicKeyCredentialType; id: string }>;
    };
};

type ServerRequestOptions = {
    publicKey: {
        challenge: string;
        timeout?: number;
        rpId?: string;
        allowCredentials?: Array<{ type: PublicKeyCredentialType; id: string }>;
        userVerification?: UserVerificationRequirement;
    };
};

export function creationOptionsFromServer(
    payload: ServerCreationOptions,
): CredentialCreationOptions {
    const pk = payload.publicKey;
    return {
        publicKey: {
            ...pk,
            challenge: b64urlToBuffer(pk.challenge),
            user: {
                ...pk.user,
                id: b64urlToBuffer(pk.user.id),
            },
            excludeCredentials: pk.excludeCredentials?.map((c) => ({
                ...c,
                id: b64urlToBuffer(c.id),
            })),
        },
    };
}

export function requestOptionsFromServer(payload: ServerRequestOptions): CredentialRequestOptions {
    const pk = payload.publicKey;
    return {
        publicKey: {
            ...pk,
            challenge: b64urlToBuffer(pk.challenge),
            allowCredentials: pk.allowCredentials?.map((c) => ({
                ...c,
                id: b64urlToBuffer(c.id),
            })),
        },
    };
}

export function toPublicKeyCredential(credential: Credential | null): PublicKeyCredential | null {
    return credential instanceof PublicKeyCredential ? credential : null;
}

export function credentialToJson(credential: PublicKeyCredential): Record<string, unknown> {
    const response = credential.response;
    if (response instanceof AuthenticatorAttestationResponse) {
        return {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: bufferToB64url(response.clientDataJSON),
                attestationObject: bufferToB64url(response.attestationObject),
            },
        };
    }
    if (response instanceof AuthenticatorAssertionResponse) {
        return {
            id: credential.id,
            rawId: bufferToB64url(credential.rawId),
            type: credential.type,
            response: {
                clientDataJSON: bufferToB64url(response.clientDataJSON),
                authenticatorData: bufferToB64url(response.authenticatorData),
                signature: bufferToB64url(response.signature),
                userHandle: response.userHandle ? bufferToB64url(response.userHandle) : null,
            },
        };
    }
    throw new Error('Unsupported credential response');
}
