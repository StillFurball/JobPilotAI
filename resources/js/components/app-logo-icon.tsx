import type { SVGAttributes } from 'react';

/**
 * JobPilot mark: a runway hold-short marking. Two solid bars on the holding
 * side, two dashed bars on the runway side — nothing crosses without clearance.
 */
export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            {...props}
        >
            <rect x="3.5" y="3" width="2.75" height="18" rx="0.5" />
            <rect x="8" y="3" width="2.75" height="18" rx="0.5" />
            <rect x="13.25" y="3" width="2.75" height="4.5" rx="0.5" />
            <rect x="13.25" y="9.75" width="2.75" height="4.5" rx="0.5" />
            <rect x="13.25" y="16.5" width="2.75" height="4.5" rx="0.5" />
            <rect x="17.75" y="3" width="2.75" height="4.5" rx="0.5" />
            <rect x="17.75" y="9.75" width="2.75" height="4.5" rx="0.5" />
            <rect x="17.75" y="16.5" width="2.75" height="4.5" rx="0.5" />
        </svg>
    );
}
