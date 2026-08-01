type BootSplashProps = {
    label?: string;
};

/** Full-viewport boot state: mark centered with a spinning ring. */
export function BootSplash({ label = 'Loading' }: BootSplashProps) {
    return (
        <div className="az-boot-splash" role="status" aria-live="polite" aria-label={label}>
            <div className="az-boot-splash__mark">
                <span className="az-boot-splash__ring" aria-hidden="true" />
                <img src="/images/mark.svg?v=2" alt="" width={40} height={40} />
            </div>
        </div>
    );
}
