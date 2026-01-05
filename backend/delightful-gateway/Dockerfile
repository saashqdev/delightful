FROM public.ecr.aws/docker/library/golang:1.24-alpine AS builder

# Set Go module proxy
ENV GOPROXY=https://goproxy.cn,direct

WORKDIR /app

# Set Alpine mirror
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# Copy dependency files
COPY go.mod go.sum ./

# Download dependencies
RUN go mod download

# Copy source code
COPY . .

# Compile entire package (includes main.go, url_validator.go and all files)
RUN CGO_ENABLED=0 GOOS=linux go build -o api-gateway .

# Use multi-stage build to reduce image size
FROM public.ecr.aws/docker/library/alpine:latest

WORKDIR /app

# Set Alpine China mirror
RUN sed -i 's/dl-cdn.alpinelinux.org/mirrors.aliyun.com/g' /etc/apk/repositories

# Copy compiled binary from builder stage
COPY --from=builder /app/api-gateway /app/

# Create config directory
RUN mkdir -p /app/config

# Set timezone to Asia/Shanghai
RUN apk add --no-cache tzdata && \
    cp /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && \
    echo "Asia/Shanghai" > /etc/timezone && \
    apk del tzdata

# Environment variables
ENV MAGIC_GATEWAY_ENV=dev
ENV MAGIC_GATEWAY_DEBUG=false

# Expose port



# Add health check
#HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
#  CMD wget -q -O- http://localhost:${PORT}/status || exit 1

# Start service
CMD ["./api-gateway"]
