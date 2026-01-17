#!/usr/bin/env python3
"""
Load Generator for Figma Scaling Demo - Phase 0
Simulates realistic user traffic patterns
"""

import asyncio
import aiohttp
import random
import time
from datetime import datetime
import argparse
import os

API_URL = os.getenv("API_URL", "http://backend:8000/api/v1")

class LoadGenerator:
    def __init__(self, concurrency=10, duration=300, read_write_ratio=0.8):
        self.concurrency = concurrency
        self.duration = duration
        self.read_write_ratio = read_write_ratio
        self.stats = {
            'total_requests': 0,
            'successful': 0,
            'failed': 0,
            'read_operations': 0,
            'write_operations': 0,
            'total_latency': 0
        }
        
    async def fetch(self, session, method, endpoint, **kwargs):
        """Execute HTTP request with timing"""
        start = time.time()
        try:
            async with session.request(method, f"{API_URL}{endpoint}", **kwargs) as response:
                latency = time.time() - start
                self.stats['total_latency'] += latency
                self.stats['total_requests'] += 1
                
                if response.status < 400:
                    self.stats['successful'] += 1
                    return await response.json(), latency
                else:
                    self.stats['failed'] += 1
                    text = await response.text()
                    print(f"🔥 SERVER ERROR {method} {endpoint}: {text[:200]}")
                    print(f"❌ {method} {endpoint} - Status {response.status}")
                    return None, latency
        except Exception as e:
            self.stats['failed'] += 1
            latency = time.time() - start
            print(f"❌ Exception: {e}")
            return None, latency
    
    async def read_operations(self, session):
        """Simulate read-heavy operations"""
        operations = [
            ('GET', '/files', {'params': {'per_page': 20}}),
            ('GET', '/files', {'params': {'per_page': 20, 'sort_by': 'view_count'}}),
            ('GET', '/users', {'params': {'per_page': 50}}),
            ('GET', '/organizations', {}),
        ]
        
        method, endpoint, kwargs = random.choice(operations)
        result, latency = await self.fetch(session, method, endpoint, **kwargs)
        self.stats['read_operations'] += 1
        
        # Simulate file view (very common operation)
        if result and 'data' in result and result['data']:
            file_id = result['data'][0].get('id')
            if file_id:
                await self.fetch(session, 'POST', f'/files/{file_id}/view', 
                               json={'user_id': result['data'][0].get('owner_id')})
    
    async def write_operations(self, session):
        """Simulate write operations"""
        # Get random user and org for file creation
        users_result, _ = await self.fetch(session, 'GET', '/users', params={'per_page': 100})
        orgs_result, _ = await self.fetch(session, 'GET', '/organizations', params={'per_page': 50})
        
        if not users_result or not orgs_result:
            return
        
        users = users_result.get('data', [])
        orgs = orgs_result.get('data', [])
        
        if not users or not orgs:
            return
        
        operations = [
            # Create new file
            lambda: self.fetch(session, 'POST', '/files', json={
                'name': f"Design File {random.randint(1000, 9999)}",
                'owner_id': random.choice(users)['id'],
                'organization_id': random.choice(orgs)['id'],
                'is_public': random.choice([True, False]),
                'metadata': {'tags': ['ui', 'design']}
            }),
            # Update existing file
            lambda: self.update_random_file(session),
        ]
        
        operation = random.choice(operations)
        await operation()
        self.stats['write_operations'] += 1
    
    async def update_random_file(self, session):
        """Update a random file"""
        files_result, _ = await self.fetch(session, 'GET', '/files', params={'per_page': 20})
        if files_result and files_result.get('data'):
            file_id = random.choice(files_result['data'])['id']
            await self.fetch(session, 'PUT', f'/files/{file_id}', json={
                'name': f"Updated Design {random.randint(1000, 9999)}"
            })
    
    async def worker(self, worker_id):
        """Single worker that generates load"""
        async with aiohttp.ClientSession() as session:
            start_time = time.time()
            
            while time.time() - start_time < self.duration:
                # Decide between read or write based on ratio
                if random.random() < self.read_write_ratio:
                    await self.read_operations(session)
                else:
                    await self.write_operations(session)
                
                # Random delay to simulate realistic user behavior
                await asyncio.sleep(random.uniform(0.1, 1.0))
    
    async def run(self):
        """Run load test"""
        print(f"""
╔══════════════════════════════════════════════════════════╗
║         Figma Scaling Demo - Load Generator                ║
╚══════════════════════════════════════════════════════════╝

Configuration:
  • Concurrency: {self.concurrency} workers
  • Duration: {self.duration} seconds
  • Read/Write Ratio: {self.read_write_ratio:.0%} reads

Starting load test...
""")
        
        start_time = time.time()
        
        # Create workers
        tasks = [self.worker(i) for i in range(self.concurrency)]
        await asyncio.gather(*tasks)
        
        elapsed = time.time() - start_time
        
        # Print results
        avg_latency = (self.stats['total_latency'] / self.stats['total_requests']) * 1000 if self.stats['total_requests'] > 0 else 0
        qps = self.stats['total_requests'] / elapsed
        
        print(f"""
╔══════════════════════════════════════════════════════════╗
║                    Test Results                          ║
╚══════════════════════════════════════════════════════════╝

Duration: {elapsed:.2f}s

Requests:
  • Total: {self.stats['total_requests']:,}
  • Successful: {self.stats['successful']:,}
  • Failed: {self.stats['failed']:,}
  • Success Rate: {(self.stats['successful']/self.stats['total_requests']*100):.1f}%

Operations:
  • Reads: {self.stats['read_operations']:,}
  • Writes: {self.stats['write_operations']:,}

Performance:
  • QPS: {qps:.2f}
  • Avg Latency: {avg_latency:.2f}ms

💡 Monitor 'docker stats' to see database CPU usage!
""")

def main():
    parser = argparse.ArgumentParser(description='Load generator for Figma scale demo')
    parser.add_argument('-c', '--concurrency', type=int, default=10, help='Number of concurrent workers')
    parser.add_argument('-d', '--duration', type=int, default=300, help='Test duration in seconds')
    parser.add_argument('-r', '--read-ratio', type=float, default=0.8, help='Ratio of read operations (0.0-1.0)')
    
    args = parser.parse_args()
    
    generator = LoadGenerator(
        concurrency=args.concurrency,
        duration=args.duration,
        read_write_ratio=args.read_ratio
    )
    
    asyncio.run(generator.run())

if __name__ == '__main__':
    main()