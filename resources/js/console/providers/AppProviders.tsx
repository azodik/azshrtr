import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { type ReactNode, useState } from 'react';

function createQueryClient(): QueryClient {
    return new QueryClient({
        defaultOptions: {
            queries: {
                staleTime: 30_000,
                retry: 1,
                refetchOnWindowFocus: false,
            },
            mutations: {
                retry: false,
            },
        },
    });
}

type AppProvidersProps = {
    children: ReactNode;
};

export function AppProviders({ children }: AppProvidersProps) {
    const [queryClient] = useState(createQueryClient);

    return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}
