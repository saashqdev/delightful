package main

import (
	"bytes"
	"compress/gzip"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"net/http"
	"strings"
	"time"

	collogspb "go.opentelemetry.io/proto/otlp/collector/logs/v1"
	colmetricspb "go.opentelemetry.io/proto/otlp/collector/metrics/v1"
	coltracepb "go.opentelemetry.io/proto/otlp/collector/trace/v1"
	"google.golang.org/grpc"
	"google.golang.org/grpc/credentials/insecure"
	"google.golang.org/grpc/metadata"
	"google.golang.org/protobuf/encoding/protojson"
	"google.golang.org/protobuf/proto"
)

// VolcengineAPMHandler handles request conversion for Volcengine Full-Stack Observability Platform
type VolcengineAPMHandler struct {
	endpoint string
	appKey   string
}

// NewVolcengineAPMHandler creates a new Volcengine observability platform handler
func NewVolcengineAPMHandler(endpoint, appKey string) *VolcengineAPMHandler {
	return &VolcengineAPMHandler{
		endpoint: endpoint,
		appKey:   appKey,
	}
}

// decompressIfNeeded checks and decompresses request body if needed
func decompressIfNeeded(r *http.Request, bodyBytes []byte) ([]byte, error) {
	// Check Content-Encoding header
	encoding := r.Header.Get("Content-Encoding")

	if encoding == "gzip" {
		if debugMode {
			logger.Printf("Detected gzip compression, starting decompression...")
		}
		reader, err := gzip.NewReader(bytes.NewReader(bodyBytes))
		if err != nil {
			return nil, fmt.Errorf("Failed to create gzip reader: %w", err)
		}
		defer reader.Close()

		decompressed, err := io.ReadAll(reader)
		if err != nil {
			return nil, fmt.Errorf("Decompression failed: %w", err)
		}

		if debugMode {
			logger.Printf("Decompression successful: %d bytes -> %d bytes", len(bodyBytes), len(decompressed))
		}
		return decompressed, nil
	}

	// Try to auto-detect gzip format (even without Content-Encoding header)
	// gzip file delightful number is 0x1f 0x8b
	if len(bodyBytes) >= 2 && bodyBytes[0] == 0x1f && bodyBytes[1] == 0x8b {
		if debugMode {
			logger.Printf("Detected gzip delightful number, attempting decompression...")
		}
		reader, err := gzip.NewReader(bytes.NewReader(bodyBytes))
		if err != nil {
			// If decompression fails, return original data
			if debugMode {
				logger.Printf("gzip decompression failed, using original data: %v", err)
			}
			return bodyBytes, nil
		}
		defer reader.Close()

		decompressed, err := io.ReadAll(reader)
		if err != nil {
			// If decompression fails, return original data
			if debugMode {
				logger.Printf("gzip decompression failed, using original data: %v", err)
			}
			return bodyBytes, nil
		}

		if debugMode {
			logger.Printf("Auto-decompression successful: %d bytes -> %d bytes", len(bodyBytes), len(decompressed))
		}
		return decompressed, nil
	}

	// No decompression needed
	return bodyBytes, nil
}

// isVolcengineAPMDomain checks if domain is Volcengine Full-Stack Observability Platform
func isVolcengineAPMDomain(domain string) bool {
	// Extract domain part (remove protocol and path)
	domain = strings.TrimPrefix(domain, "http://")
	domain = strings.TrimPrefix(domain, "https://")
	if idx := strings.Index(domain, "/"); idx > 0 {
		domain = domain[:idx]
	}

	// Check if matches Volcengine observability platform domain
	return strings.Contains(domain, "apmplus-cn-beijing.volces.com")
}

// HandleVolcengineAPMRequest handles Volcengine observability platform requests
// Converts HTTP requests to gRPC requests
func (h *VolcengineAPMHandler) HandleVolcengineAPMRequest(
	w http.ResponseWriter,
	r *http.Request,
	targetURL string,
	bodyBytes []byte,
) error {
	// First check and decompress request body
	decompressed, err := decompressIfNeeded(r, bodyBytes)
	if err != nil {
		logger.Printf("Request decompression failed: %v", err)
		return fmt.Errorf("Request decompression failed: %w", err)
	}
	bodyBytes = decompressed

	// Determine request type (first from URL path)
	requestType := h.detectRequestType(r.URL.Path)

	if debugMode {
		logger.Printf("Detected type from URL: %s, preparing intelligent detection of actual type...", requestType)
	}

	// If type cannot be determined from URL, try intelligent detection
	if requestType == "trace" && !strings.Contains(strings.ToLower(r.URL.Path), "trace") {
		// Try to determine type by parsing request body
		detectedType := h.detectRequestTypeFromBody(bodyBytes, r.Header.Get("Content-Type"))
		if detectedType != "" {
			requestType = detectedType
			if debugMode {
				logger.Printf("Intelligently detected type through request body: %s", requestType)
			}
		}
	}

	if debugMode {
		logger.Printf("Final determined request type: %s", requestType)
	}

	// Different processing based on request type
	switch requestType {
	case "trace":
		return h.handleTraceRequest(w, r, bodyBytes)
	case "metrics":
		return h.handleMetricsRequest(w, r, bodyBytes)
	case "logs":
		return h.handleLogsRequest(w, r, bodyBytes)
	default:
		return fmt.Errorf("Unsupported request type: %s", requestType)
	}
}

// detectRequestType detects request type from URL path
func (h *VolcengineAPMHandler) detectRequestType(path string) string {
	path = strings.ToLower(path)

	if strings.Contains(path, "/v1/traces") || strings.Contains(path, "trace") {
		return "trace"
	}
	if strings.Contains(path, "/v1/metrics") || strings.Contains(path, "metric") {
		return "metrics"
	}
	if strings.Contains(path, "/v1/logs") || strings.Contains(path, "log") {
		return "logs"
	}

	// Default return trace
	return "trace"
}

// detectRequestTypeFromBody intelligently detects type by attempting to parse request body
func (h *VolcengineAPMHandler) detectRequestTypeFromBody(bodyBytes []byte, contentType string) string {
	if len(bodyBytes) == 0 {
		return ""
	}

	// Decide which parsing method to use based on Content-Type
	isJSON := strings.Contains(contentType, "application/json")

	// Try to parse as Metrics (most common)
	if isJSON {
		var metricsRequest colmetricspb.ExportMetricsServiceRequest
		if err := protojson.Unmarshal(bodyBytes, &metricsRequest); err == nil {
			// Check if there is valid metrics data
			if len(metricsRequest.ResourceMetrics) > 0 {
				return "metrics"
			}
		}
	} else {
		var metricsRequest colmetricspb.ExportMetricsServiceRequest
		if err := proto.Unmarshal(bodyBytes, &metricsRequest); err == nil {
			// Check if there is valid metrics data
			if len(metricsRequest.ResourceMetrics) > 0 {
				return "metrics"
			}
		}
	}

	// Try to parse as Trace
	if isJSON {
		var traceRequest coltracepb.ExportTraceServiceRequest
		if err := protojson.Unmarshal(bodyBytes, &traceRequest); err == nil {
			// Check if there is valid trace data
			if len(traceRequest.ResourceSpans) > 0 {
				return "trace"
			}
		}
	} else {
		var traceRequest coltracepb.ExportTraceServiceRequest
		if err := proto.Unmarshal(bodyBytes, &traceRequest); err == nil {
			// Check if there is valid trace data
			if len(traceRequest.ResourceSpans) > 0 {
				return "trace"
			}
		}
	}

	// Try to parse as Logs
	if isJSON {
		var logsRequest collogspb.ExportLogsServiceRequest
		if err := protojson.Unmarshal(bodyBytes, &logsRequest); err == nil {
			// Check if there is valid logs data
			if len(logsRequest.ResourceLogs) > 0 {
				return "logs"
			}
		}
	} else {
		var logsRequest collogspb.ExportLogsServiceRequest
		if err := proto.Unmarshal(bodyBytes, &logsRequest); err == nil {
			// Check if there is valid logs data
			if len(logsRequest.ResourceLogs) > 0 {
				return "logs"
			}
		}
	}

	// Unable to detect
	return ""
}

// handleTraceRequest handles trace requests
func (h *VolcengineAPMHandler) handleTraceRequest(
	w http.ResponseWriter,
	r *http.Request,
	bodyBytes []byte,
) error {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	// Parse HTTP request body (supports both JSON and Protobuf formats)
	// Note: bodyBytes has been decompressed before calling
	var traceRequest coltracepb.ExportTraceServiceRequest
	contentType := r.Header.Get("Content-Type")

	if strings.Contains(contentType, "application/json") {
		// JSON format
		if err := protojson.Unmarshal(bodyBytes, &traceRequest); err != nil {
			logger.Printf("Failed to parse trace request (JSON): %v", err)
			return fmt.Errorf("Failed to parse trace request (JSON): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing trace request using JSON format")
		}
	} else {
		// Protobuf binary format (default)
		if err := proto.Unmarshal(bodyBytes, &traceRequest); err != nil {
			logger.Printf("Failed to parse trace request (Protobuf): %v", err)
			return fmt.Errorf("Failed to parse trace request (Protobuf): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing trace request using Protobuf format")
		}
	}

	// Create gRPC connection and set headers
	md := metadata.New(map[string]string{
		"x-byteapm-appkey": h.appKey,
	})
	ctx = metadata.NewOutgoingContext(ctx, md)

	conn, err := grpc.Dial(
		h.endpoint,
		grpc.WithTransportCredentials(insecure.NewCredentials()),
	)
	if err != nil {
		logger.Printf("Failed to create gRPC connection: %v", err)
		return fmt.Errorf("Failed to create gRPC connection: %w", err)
	}
	defer conn.Close()

	// Create trace service client
	client := coltracepb.NewTraceServiceClient(conn)

	// Send gRPC request
	resp, err := client.Export(ctx, &traceRequest)
	if err != nil {
		logger.Printf("Failed to send trace gRPC request: %v", err)
		return fmt.Errorf("Failed to send trace gRPC request: %w", err)
	}

	// Convert gRPC response to HTTP response
	return h.writeJSONResponse(w, resp)
}

// handleMetricsRequest handles metrics requests
func (h *VolcengineAPMHandler) handleMetricsRequest(
	w http.ResponseWriter,
	r *http.Request,
	bodyBytes []byte,
) error {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	// Parse HTTP request body (supports both JSON and Protobuf formats)
	// Note: bodyBytes has been decompressed before calling
	var metricsRequest colmetricspb.ExportMetricsServiceRequest
	contentType := r.Header.Get("Content-Type")

	if strings.Contains(contentType, "application/json") {
		// JSON format
		if err := protojson.Unmarshal(bodyBytes, &metricsRequest); err != nil {
			logger.Printf("Failed to parse metrics request (JSON): %v", err)
			return fmt.Errorf("Failed to parse metrics request (JSON): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing metrics request using JSON format")
		}
	} else {
		// Protobuf binary format (default)
		if err := proto.Unmarshal(bodyBytes, &metricsRequest); err != nil {
			logger.Printf("Failed to parse metrics request (Protobuf): %v", err)
			return fmt.Errorf("Failed to parse metrics request (Protobuf): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing metrics request using Protobuf format")
		}
	}

	// Create gRPC connection and set headers
	md := metadata.New(map[string]string{
		"x-byteapm-appkey": h.appKey,
	})
	ctx = metadata.NewOutgoingContext(ctx, md)

	conn, err := grpc.Dial(
		h.endpoint,
		grpc.WithTransportCredentials(insecure.NewCredentials()),
	)
	if err != nil {
		logger.Printf("Failed to create gRPC connection: %v", err)
		return fmt.Errorf("Failed to create gRPC connection: %w", err)
	}
	defer conn.Close()

	// Create metrics service client
	client := colmetricspb.NewMetricsServiceClient(conn)

	// Send gRPC request
	resp, err := client.Export(ctx, &metricsRequest)
	if err != nil {
		logger.Printf("Failed to send metrics gRPC request: %v", err)
		return fmt.Errorf("Failed to send metrics gRPC request: %w", err)
	}

	// Convert gRPC response to HTTP response
	return h.writeJSONResponse(w, resp)
}

// handleLogsRequest handles logs requests
func (h *VolcengineAPMHandler) handleLogsRequest(
	w http.ResponseWriter,
	r *http.Request,
	bodyBytes []byte,
) error {
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	// Parse HTTP request body (supports both JSON and Protobuf formats)
	// Note: bodyBytes has been decompressed before calling
	var logsRequest collogspb.ExportLogsServiceRequest
	contentType := r.Header.Get("Content-Type")

	if strings.Contains(contentType, "application/json") {
		// JSON format
		if err := protojson.Unmarshal(bodyBytes, &logsRequest); err != nil {
			logger.Printf("Failed to parse logs request (JSON): %v", err)
			return fmt.Errorf("Failed to parse logs request (JSON): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing logs request using JSON format")
		}
	} else {
		// Protobuf binary format (default)
		if err := proto.Unmarshal(bodyBytes, &logsRequest); err != nil {
			logger.Printf("Failed to parse logs request (Protobuf): %v", err)
			return fmt.Errorf("Failed to parse logs request (Protobuf): %w", err)
		}
		if debugMode {
			logger.Printf("Parsing logs request using Protobuf format")
		}
	}

	// Create gRPC connection and set headers
	md := metadata.New(map[string]string{
		"x-byteapm-appkey": h.appKey,
	})
	ctx = metadata.NewOutgoingContext(ctx, md)

	conn, err := grpc.Dial(
		h.endpoint,
		grpc.WithTransportCredentials(insecure.NewCredentials()),
	)
	if err != nil {
		logger.Printf("Failed to create gRPC connection: %v", err)
		return fmt.Errorf("Failed to create gRPC connection: %w", err)
	}
	defer conn.Close()

	// Create logs service client
	client := collogspb.NewLogsServiceClient(conn)

	// Send gRPC request
	resp, err := client.Export(ctx, &logsRequest)
	if err != nil {
		logger.Printf("Failed to send logs gRPC request: %v", err)
		return fmt.Errorf("Failed to send logs gRPC request: %w", err)
	}

	// Convert gRPC response to HTTP response
	return h.writeJSONResponse(w, resp)
}

// writeJSONResponse writes response to HTTP ResponseWriter
func (h *VolcengineAPMHandler) writeJSONResponse(w http.ResponseWriter, resp interface{}) error {
	w.Header().Set("Content-Type", "application/json")
	w.WriteHeader(http.StatusOK)

	respBytes, err := json.Marshal(resp)
	if err != nil {
		logger.Printf("Failed to serialize response: %v", err)
		return fmt.Errorf("Failed to serialize response: %w", err)
	}

	if _, err := w.Write(respBytes); err != nil {
		logger.Printf("Failed to write response: %v", err)
		return fmt.Errorf("Failed to write response: %w", err)
	}

	return nil
}

// GetVolcengineAPMConfig gets Volcengine observability platform configuration from environment variables
func GetVolcengineAPMConfig() (endpoint string, appKey string, ok bool) {
	// Get configuration from environment variables
	endpoint = getEnvWithDefault("VOLCENGINE_APM_ENDPOINT", "apmplus-cn-beijing.ivolces.com:4317")
	appKey = getEnvWithDefault("VOLCENGINE_APM_APPKEY", "")

	// If AppKey is not configured, return false
	if appKey == "" {
		// Try to get from OTEL configuration
		if otelHeaders := getEnvWithDefault("OTEL_EXPORTER_OTLP_HEADERS", ""); otelHeaders != "" {
			// Parse OTEL_EXPORTER_OTLP_HEADERS to get X-ByteAPM-AppKey
			if strings.Contains(otelHeaders, "X-ByteAPM-AppKey=") {
				parts := strings.Split(otelHeaders, "=")
				if len(parts) >= 2 {
					appKey = strings.TrimSpace(parts[1])
					// Remove possible comma, semicolon and other delimiters
					if idx := strings.IndexAny(appKey, ",;"); idx > 0 {
						appKey = appKey[:idx]
					}
				}
			}
		}
	}

	ok = appKey != ""
	return
}

// ShouldUseVolcengineAPMHandler checks if Volcengine observability platform handler should be used
func ShouldUseVolcengineAPMHandler(targetURL string) bool {
	return isVolcengineAPMDomain(targetURL)
}
