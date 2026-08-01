/// <reference types="vite/client" />

interface AzshrtrBuildInfo {
    version: string;
    build: string | null;
    commit: string | null;
}

interface Window {
    __AZSHRTR__?: AzshrtrBuildInfo;
}
