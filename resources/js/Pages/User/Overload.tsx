'use client';
import { useEffect, useState } from 'react';

import { Head } from '@inertiajs/react';
import mqtt from 'mqtt';
import React from 'react';

interface OverloadProps {
    client: string;
    event: {
        name: string;
        slug: string;
        user_id: string;
    };
    queue: {
        title: string;
        message: string;
        expected_finish: string | null;
    };
    primary_color: string;
    secondary_color: string;
    text_primary_color: string;
    text_secondary_color: string;
    texture?: string;
    logo?: string;
    logo_alt?: string;
}

export default function Overload({
    event,
    queue,
    primary_color,
    secondary_color,
    text_primary_color,
    text_secondary_color,
    texture,
    logo,
    logo_alt,
}: OverloadProps): React.ReactElement {

    const [timeLeft, setTimeLeft] = useState('');

    useEffect(() => {
        const mqttclient = mqtt.connect('wss://broker.emqx.io:8084/mqtt');

        // Handle MQTT connection
        mqttclient.on('connect', () => {
            const sanitizedUserId = event.slug.replace(/-/g, '');
            mqttclient.subscribe(`novatix/logs/${sanitizedUserId}`);
        });

        mqttclient.on('message', (topic, message) => {
            try {
                const payload = JSON.parse(message.toString());
                const updates = Array.isArray(payload) ? payload : [payload];

                const logoutEvent = updates.find(
                    (e) =>
                        e.event === 'user_logout' &&
                        e.next_user_id === event.user_id,
                );

                if (logoutEvent) {
                    // Tambahkan delay kecil agar DB update selesai
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            } catch (error) {
                console.error('Error parsing MQTT message:', error);
            }
        });

        // Handle countdown logic for queue.expected_finish
        if (queue.expected_finish) {
            const expectedTime = new Date(queue.expected_finish);
            const now = new Date();
            const diffMs = expectedTime.getTime() - now.getTime();

            if (diffMs > 0) {
                setTimeout(() => {
                    window.location.reload();
                }, diffMs);
            }

            const target = new Date(expectedTime.toUTCString()).getTime();

            const interval = setInterval(() => {
                const now = new Date().getTime();
                const diff = target - now;

                if (diff <= 0) {
                    setTimeLeft("Time's up!");
                    clearInterval(interval);
                    return;
                }

                const hours = Math.floor(diff / (1000 * 60 * 60));
                const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((diff % (1000 * 60)) / 1000);

                setTimeLeft(`${hours}h ${minutes}m ${seconds}s`);
            }, 1000);

            return () => clearInterval(interval);
        }

        return () => {
            mqttclient.end();
        };
    }, [event, queue]);

    return (
        <div
            className="flex min-h-screen flex-col items-center justify-center px-4 py-12 sm:px-6 lg:px-8"
            style={{
                backgroundColor: primary_color,
                backgroundImage: texture ? `url(${texture})` : undefined,
                backgroundRepeat: 'repeat',
                backgroundSize: 'auto',
            }}
        >
            <Head title={'In Queue:  ' + queue.title} />

            <div
                className="flex w-full max-w-md flex-col items-center justify-center gap-4 rounded-lg p-8 shadow-md"
                style={{
                    backgroundColor: secondary_color,
                    color: text_primary_color,
                }}
            >
                {logo && (
                    <img
                        src={logo}
                        alt={logo_alt || 'Logo'}
                        className="h-32 w-auto rounded-lg"
                    />
                )}
                <h2
                    className="text-center text-2xl font-extrabold"
                    style={{ color: text_primary_color || '#1f2937' }}
                >
                    {event.name}
                </h2>
                <div className="flex flex-col text-center">
                    <h2
                        className="text-xl font-extrabold"
                        style={{ color: text_primary_color || '#1f2937' }}
                    >
                        {queue.title}
                    </h2>
                    <p
                        className="text-sm"
                        style={{
                            color: text_secondary_color || '#4b5563',
                        }}
                    >
                        {queue.message}
                    </p>

                    {queue.expected_finish && (
                        <div
                            className="mt-2 rounded-md p-4"
                            style={{
                                backgroundColor: `${primary_color || '#fef3c7'}20`,
                                borderColor: primary_color || '#fcd34d',
                            }}
                        >
                            <div className="flex flex-col items-center px-4">
                                <h3
                                    className="text-sm font-medium"
                                    style={{
                                        color: text_primary_color || '#92400e',
                                    }}
                                >
                                    Expected completion
                                </h3>
                                <div
                                    className="text-sm"
                                    style={{
                                        color:
                                            text_secondary_color || '#b45309',
                                    }}
                                >
                                    <p>{timeLeft}</p>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div
                    className="text-center text-sm"
                    style={{ color: text_secondary_color || '#6b7280' }}
                >
                    <p>Keep this page open to stay in line.</p>
                </div>
            </div>
        </div>
    );
}
