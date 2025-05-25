import mqtt from 'mqtt';

const Mqttclient = mqtt.connect('wss://broker.emqx.io:8084/mqtt', {
    clientId: 'react_client_' + Math.random().toString(16).substring(2, 8),
    keepalive: 60,
    reconnectPeriod: 1000,
    clean: true,
});

Mqttclient.on('connect', () => {
    console.log('MQTT Connected');
});

Mqttclient.on('error', (err) => {
    console.error('MQTT Connection Error:', err);
});

export default Mqttclient;
