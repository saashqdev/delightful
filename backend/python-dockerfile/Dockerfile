# Use Playwright base image
FROM mcr.microsoft.com/playwright:v1.49.1-noble

# Set working directory
WORKDIR /app

# Set Python environment
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1
ENV TZ=Asia/Shanghai

# Replace with China mirror source
# RUN sed -i 's/archive.ubuntu.com/mirrors.tuna.tsinghua.edu.cn/g' /etc/apt/sources.list && \
#     sed -i 's/security.ubuntu.com/mirrors.tuna.tsinghua.edu.cn/g' /etc/apt/sources.list

# Install required packages, use --no-install-recommends to reduce image size
RUN apt-get update && apt-get install -y --no-install-recommends \
    python3-pip \
    python3-venv \
    python3-full \
    git \
    vim \
    patch \
    tzdata \
    ripgrep \
    tree \
    fontconfig \
    fonts-wqy-zenhei \
    fonts-wqy-microhei \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Update font cache
RUN fc-cache -fv

# Directly install specified versions of Node.js and TypeScript
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - && \
    apt-get install -y nodejs && \
    npm config set registry https://registry.npmmirror.com && \
    npm install -g typescript@5.8.3 && \
    # Pin Node.js version to 22.14.0
    npm install -g n && \
    n 22.14.0 && \
    # Print version information
    echo "========== Node.js Version Info ==========" && \
    node -v && \
    echo "========== TypeScript Version Info ==========" && \
    tsc -v

# Set pip mirror source
# RUN pip config set global.index-url https://pypi.tuna.tsinghua.edu.cn/simple

# Create and activate virtual environment
RUN python3 -m venv /venv
ENV PATH="/venv/bin:$PATH"

# Install tiktoken
RUN pip install tiktoken

# Preload tiktoken encoder, need to download token encoding file cl100k_base.tiktoken
RUN echo "import tiktoken; tiktoken.get_encoding('cl100k_base'); print('Tiktoken cl100k_base encoding preloaded successfully')" > preload_tiktoken.py && \
    python preload_tiktoken.py && \
    rm preload_tiktoken.py

# Copy and install project dependencies
COPY requirements.txt .


# Install dependencies
RUN pip install -r requirements.txt

# Install sandbox environment dependencies
COPY requirements_sandbox.txt .
RUN pip install -r requirements_sandbox.txt

# Update environment variable path
ENV PATH="/usr/local/bin:${PATH}"
