# Use Playwright base image
FROM ghcr.io/saashqdev/be-delightful-python-base-image:latest

# Set working directory
WORKDIR /app

# Copy and install project dependencies
COPY requirements.txt .

# Install dependencies, use cache mount optimization
RUN pip install -r requirements.txt

# Install sandbox environment dependencies
COPY requirements_sandbox.txt .
RUN pip install -r requirements_sandbox.txt

# Create matplotlib configuration directory
RUN mkdir -p /root/.config/matplotlib

# Copy font check script
COPY deploy/ /app/deploy/

# Check matplotlib font (if font is not WenQuanYi, build will fail)
RUN python /app/deploy/configure_font.py
RUN python /app/deploy/check_font.py

# Update environment variable path
ENV PATH="/usr/local/bin:${PATH}"

COPY . .

RUN pip install -e agentlang

# Get Git commit ID and save to environment variable
ARG GIT_COMMIT_ID="unknown"
ENV GIT_COMMIT_ID=${GIT_COMMIT_ID}

EXPOSE 8000
EXPOSE 8002

CMD ["python3", "main.py", "start", "ws-server"]
