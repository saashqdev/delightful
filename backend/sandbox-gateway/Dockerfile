# Use official Python image
FROM python:3.12-slim

# Set working directory
WORKDIR /app

# Set Python environment
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Replace with Aliyun mirror source
RUN rm -rf /etc/apt/sources.list.d/* && \
    echo "deb https://mirrors.aliyun.com/debian/ bookworm main contrib non-free non-free-firmware" > /etc/apt/sources.list && \
    echo "deb https://mirrors.aliyun.com/debian/ bookworm-updates main contrib non-free non-free-firmware" >> /etc/apt/sources.list && \
    echo "deb https://mirrors.aliyun.com/debian/ bookworm-backports main contrib non-free non-free-firmware" >> /etc/apt/sources.list && \
    echo "deb https://mirrors.aliyun.com/debian-security bookworm-security main contrib non-free non-free-firmware" >> /etc/apt/sources.list

# Install necessary packages, using --no-install-recommends to reduce size
RUN apt-get update && apt-get install -y --no-install-recommends \
    curl \
    gnupg \
    lsb-release \
    git \
    vim \
    patch \
    ca-certificates \
    tzdata \
    tcpdump \
    net-tools \
    iputils-ping \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Set timezone to China Standard Time
ENV TZ=America/Toronto
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Set pip mirror source
RUN pip config set global.index-url https://pypi.tuna.tsinghua.edu.cn/simple

# Copy and install project dependencies
COPY requirements.txt .

# Install dependencies
RUN pip install -r requirements.txt

# Copy project files
COPY . .

# Expose port
EXPOSE 8003

# Startup command
CMD ["python", "main.py"]
