# figma-scaling-demo

## Context

https://www.linkedin.com/posts/alexandre-zajac_figma-scaled-to-40m-users-not-with-cassandra-activity-7415067310945755136-yRcm?utm_source=share&utm_medium=member_desktop&rcm=ACoAAEy4QugB9J7Cxd5ySHs8UwDEm7m8dlX_iGA

<img width="800" height="1192" alt="image" src="https://github.com/user-attachments/assets/4eef9d0f-5812-40e1-8946-3e61457874bc" />


## Setup

    docker compose up -d


## Phase 0 - Monolith

<img width="2292" height="2874" alt="screencapture-localhost-3000-2026-01-15-11_05_18" src="https://github.com/user-attachments/assets/eec2c2b4-f778-432d-a539-d889824d950b" />
<img width="2292" height="2258" alt="screencapture-localhost-3000-2026-01-15-11_05_46" src="https://github.com/user-attachments/assets/c47e4581-a762-4352-a3a0-0c5c0edc98a8" />

## Test & Monitoring

### Run load test in terminal:

    docker compose run --rm loadgen python loadgen.py \
        --concurrency 50 \
        --duration 300 \
        --read-ratio 0.7

### Watch the graphs update in real-time!

DB 

    docker stats figma_postgres_primary

Grafana 

    http://localhost:3001/
    
    username : admin
    password : admin

Prometheus

    http://localhost:9090/


## Tests Snapshots/Results

### Phase 0 - Monolith

    ╔══════════════════════════════════════════════════════════╗
    ║         Figma Scaling Demo - Load Generator              ║
    ╚══════════════════════════════════════════════════════════╝

    Configuration:
    • Concurrency: 200 workers
    • Duration: 300 seconds
    • Read/Write Ratio: 70% reads

    Starting load test...

    ╔══════════════════════════════════════════════════════════╗
    ║                    Test Results                          ║
    ╚══════════════════════════════════════════════════════════╝

    Duration: 303.69s

    Requests:
    • Total: 30,450
    • Successful: 30,450
    • Failed: 0
    • Success Rate: 100.0%

    Operations:
    • Reads: 8,814
    • Writes: 3,678

    Performance:
    • QPS: 100.27
    • Avg Latency: 1757.19ms
