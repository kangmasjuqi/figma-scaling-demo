FROM python:3.11-slim

WORKDIR /app

# Install dependencies
RUN pip install --no-cache-dir aiohttp

# Copy load generator script
COPY loadgen.py .

# Make script executable
RUN chmod +x loadgen.py

CMD ["python", "loadgen.py", "-c", "20", "-d", "600"]