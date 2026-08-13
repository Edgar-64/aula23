import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
  scenarios: {
    pico_acesso_curto: {
      executor: "per-vu-iterations",
      vus: 10,
      iterations: 1,
      maxDuration: "10s",
    },
  },
};

const arquivo = open("./tokens_acesso.csv");

const tokens = [
    '6d9e0c52a4f14b92da76d34339aba7aa',
    'd2dd0de6fe4c0e2c5850624c93271ced',
    '6ef1c36e3e3bc5d8c39b067e17eb36d3',
    '61522cc2389f8f04c4c01302575ad31d',
    'ed7ec9cad0df1c0fc31c004865228dcf',
    'd428413dcc98849ffff7d0cdd7d3d621',
    'e5ae91e24059ee089833f2de140512bb',
    'df89b07436ef7c8cdf128d4da53b4992',
    'e48606c76d66ed49ec4ff155a0453044',
    'a677e38788506da0518b27743f0a1ba4',
];

export default function () {
    const token = tokens[__VU - 1];

    const payload = JSON.stringify({
        token: token,
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const res = http.post(
        'http://localhost/aula23/validar_acesso.php',
        payload,
        params
    );

    console.log(`VU ${__VU} - STATUS: ${res.status}`);

    check(res, {
        'status foi 200': (r) => r.status === 200,
        'tempo de resposta < 1s': (r) => r.timings.duration < 1000,
    });

    sleep(1);
}
