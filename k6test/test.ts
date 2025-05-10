import { sleep } from 'k6';
import http from 'k6/http';

export let options = {
    stages: [
        { duration: '2m', target: 100 }, // ramp-up to 100 users
        { duration: '5m', target: 100 }, // hold at 100 users
        { duration: '2m', target: 0 }, // ramp-down
    ],
};

export default function () {
    http.get('https://gmco.staging-novatix.arachnova.id');
    sleep(1); // simulate user thinking time
}
