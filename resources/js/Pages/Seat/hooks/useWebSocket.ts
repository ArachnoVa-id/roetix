import Echo from 'laravel-echo';
import { useEffect } from 'react';

declare global {
    interface Window {
        Echo: Echo;
    }
}

export const useWebSocket = (
    channel: string,
    callback: (data: any) => void,
) => {
    useEffect(() => {
        if (!window.Echo) {
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: process.env.REACT_APP_WEBSOCKET_KEY,
                cluster: process.env.REACT_APP_PUSHER_CLUSTER,
                forceTLS: true,
            });
        }

        const subscription = window.Echo.channel(channel).listen(
            '.SeatStatusUpdated',
            callback,
        );

        return () => {
            subscription.stopListening('.SeatStatusUpdated');
        };
    }, [channel, callback]);
};
