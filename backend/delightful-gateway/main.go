package main

import (
	"api-gateway/internal/handler"
	"bytes"
	"context"
	"crypto/rand"
	"crypto/sha256"
	"crypto/subtle"
	"encoding/hex"
	"encoding/json"
	"errors"
	"fmt"
	"io"
	"log"
	"net"
	"net/http"
	"os"
	"path/filepath"
	"regexp"
	"strings"
	"sync/atomic"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"github.com/joho/godotenv"
)

// Global variables
var (
	jwtSecret   []byte
	jwtSecretID string // Key version identifier
	envVars     map[string]string
	logger      *log.Logger
	debugMode   bool
	ctx         = context.Background()

	// Supported services list
	supportedServices = []string{"OPENAI", "MAGIC", "DEEPSEEK"}

	// Global token version counter (for revocation)
	tokenVersionCounter int64 = 0

	// Global revocation timestamp
	globalRevokeTimestamp int64 = 0

	// JWT-related security configuration
	keyRotationInterval = 24 * time.Hour // Key rotation interval
	lastKeyRotation     time.Time

	// Precompiled regular expressions
	byteAPMAppKeyRegex = regexp.MustCompile(`X-ByteAPM-AppKey=[^\s,;]*`)

	// URL whitelist rules
	allowedTargetRules []*TargetURLRule

	// Allowed internal IP list (CIDR format or single IP)
	allowedPrivateIPs []*net.IPNet

	// Special API configuration: services that require API key replacement in request body
	specialApiKeys map[string]string

	// Security limits
	maxRequestBodySize  int64 = 10 * 1024 * 1024  // 10MB request body limit
	maxResponseBodySize int64 = 100 * 1024 * 1024 // 100MB response body limit
)

// JWTClaims defines JWT claims - Enhanced version
type JWTClaims struct {
	jwt.RegisteredClaims
	ContainerID           string `json:"container_id"`
	MagicUserID           string `json:"magic_user_id,omitempty"`
	MagicOrganizationCode string `json:"magic_organization_code,omitempty"`
	// Add token version for revocation
	TokenVersion int64 `json:"token_version"`
	// Add creation time
	CreatedAt int64 `json:"created_at"`
	// Add security-related fields
	KeyID string `json:"kid,omitempty"`   // Key version identifier
	Nonce string `json:"nonce,omitempty"` // Anti-replay attack
	Scope string `json:"scope,omitempty"` // Permission scope
}

// ServiceInfo stores service configuration information
type ServiceInfo struct {
	Name    string `json:"name"`
	BaseURL string `json:"base_url"`
	ApiKey  string `json:"api_key,omitempty"`
	Model   string `json:"default_model,omitempty"`
}

// Initialize JWT security configuration - Get secret from MAGIC_GATEWAY_API_KEY
func initJWTSecurity() {
	// Get JWT secret from MAGIC_GATEWAY_API_KEY
	apiKey := getEnvWithDefault("MAGIC_GATEWAY_API_KEY", "")
	if apiKey == "" {
		logger.Fatal("Error: MAGIC_GATEWAY_API_KEY environment variable must be set")
	}

	// Validate API key strength
	// if len(apiKey) < 32 {
	// 	logger.Printf("Warning: MAGIC_GATEWAY_API_KEY length insufficient, recommend at least 32 characters")
	// }

	// Use API key as JWT secret
	jwtSecret = []byte(apiKey)

	// Create key version identifier (using API key hash)
	hash := sha256.Sum256([]byte(apiKey))
	jwtSecretID = hex.EncodeToString(hash[:8]) // Use first 8 bytes as version identifier

	lastKeyRotation = time.Now()

	logger.Printf("JWT security configuration initialized, using MAGIC_GATEWAY_API_KEY as secret")
}

// Generate random number for anti-replay attack
func generateNonce() string {
	bytes := make([]byte, 16)
	rand.Read(bytes)
	return hex.EncodeToString(bytes)
}

// sanitizeLogString cleans user input to prevent log injection attacks
func sanitizeLogString(s string) string {
	// Remove newlines and carriage returns to prevent log injection
	s = strings.ReplaceAll(s, "\n", "\\n")
	s = strings.ReplaceAll(s, "\r", "\\r")
	// Limit length to prevent log flooding attacks
	if len(s) > 200 {
		s = s[:200] + "...[truncated]"
	}
	return s
}

// maskSensitiveValue masks sensitive values for logging (only shows first 4 and last 4 characters)
func maskSensitiveValue(s string) string {
	if len(s) <= 8 {
		return "***"
	}
	return s[:4] + "***" + s[len(s)-4:]
}

// Check key rotation
func checkKeyRotation() {
	if time.Since(lastKeyRotation) > keyRotationInterval {
		// Key rotation logic can be implemented here
		logger.Printf("Key rotation check: current key has been in use for %v", time.Since(lastKeyRotation))
	}
}

// loadEnvFile attempts to load .env file from multiple locations
func loadEnvFile() error {
	// Try multiple locations to find .env file
	envPaths := []string{
		".env",                     // Current working directory
		filepath.Join(".", ".env"), // Explicit current directory
	}

	// Try to get executable directory and look for .env there
	if exePath, err := os.Executable(); err == nil {
		exeDir := filepath.Dir(exePath)
		envPaths = append(envPaths, filepath.Join(exeDir, ".env"))
		// Also try parent directory (in case executable is in a subdirectory)
		envPaths = append(envPaths, filepath.Join(filepath.Dir(exeDir), ".env"))
	}


	// Try to get current working directory
	if wd, err := os.Getwd(); err == nil {
		envPaths = append(envPaths, filepath.Join(wd, ".env"))
		// Try parent directory
		envPaths = append(envPaths, filepath.Join(filepath.Dir(wd), ".env"))
	}

	// Deduplicate paths (avoid duplicate attempts)
	uniquePaths := make(map[string]bool)
	var uniqueEnvPaths []string
	for _, p := range envPaths {
		absPath, err := filepath.Abs(p)
		if err != nil {
			absPath = p
		}
		if !uniquePaths[absPath] {
			uniquePaths[absPath] = true
			uniqueEnvPaths = append(uniqueEnvPaths, p)
		}
	}

	// Try each path
	var lastErr error
	for i, envPath := range uniqueEnvPaths {
		absPath, _ := filepath.Abs(envPath)

		// Check if file exists
		if _, err := os.Stat(envPath); os.IsNotExist(err) {
			if debugMode {
				logger.Printf("Trying location %d: %s (does not exist)", i+1, absPath)
			}
			lastErr = err
			continue
		}

		// Try to load
		err := godotenv.Load(envPath)
		if err == nil {
			logger.Printf("Successfully loaded .env file: %s", absPath)
			return nil
		}

		if debugMode {
			logger.Printf("Trying location %d: %s (load failed: %v)", i+1, absPath, err)
		}
		lastErr = err
	}

	// If all attempts fail, return the last error
	return lastErr
}

// Initialization function
func init() {
	// Set up logging
	logger = log.New(os.Stdout, "[API Gateway] ", log.LstdFlags)
	logger.Println("Initializing service...")

	// Load .env file (supports multiple locations)
	err := loadEnvFile()
	if err != nil {
		// Always log warning, not just in debug mode, so users know .env file wasn't loaded
		logger.Printf("Warning: Unable to load .env file: %v (will only use system environment variables)", err)
	} else {
		logger.Println("Successfully loaded .env file")
	}

	// Initialize JWT security configuration
	initJWTSecurity()

	// Cache environment variables
	envVars = make(map[string]string)
	for _, env := range os.Environ() {
		parts := strings.SplitN(env, "=", 2)
		if len(parts) == 2 {
			envVars[parts[0]] = parts[1]
		}
	}

	// Set debug mode
	debugMode = getEnvWithDefault("MAGIC_GATEWAY_DEBUG", "false") == "true"
	if debugMode {
		logger.Println("Debug mode enabled")
	}

	// Load URL whitelist
	loadAllowedTargetURLs()

	// Load allowed internal IP list
	loadAllowedPrivateIPs()

	// Load special API configuration
	loadSpecialApiKeys()

	logger.Printf("Loaded %d environment variables", len(envVars))
}

// Helper function: get environment variable, use default value if not exists
func getEnvWithDefault(key, defaultValue string) string {
	value, exists := os.LookupEnv(key)
	if !exists {
		return defaultValue
	}
	return value
}

// loadSpecialApiKeys loads special API configuration from environment variables
// These APIs require automatic API key replacement in request body
func loadSpecialApiKeys() {
	specialApiKeys = make(map[string]string)

	// Read configuration from MAGIC_GATEWAY_SPECIAL_API_KEYS environment variable
	// Format: BASE_URL_KEY:API_KEY_KEY|BASE_URL_KEY2:API_KEY_KEY2
	// Example: TEXT_TO_IMAGE_API_BASE_URL:TEXT_TO_IMAGE_ACCESS_KEY|VOICE_UNDERSTANDING_API_BASE_URL:VOICE_UNDERSTANDING_API_KEY
	configStr := getEnvWithDefault("MAGIC_GATEWAY_SPECIAL_API_KEYS", "")

	if configStr == "" {
		// Special API keys not configured
		specialApiKeys = make(map[string]string)
		logger.Printf("No special APIs configured, set MAGIC_GATEWAY_SPECIAL_API_KEYS environment variable to enable")
		logger.Printf("Format example: BASE_URL_KEY:API_KEY_KEY|BASE_URL_KEY2:API_KEY_KEY2")
		return
	}

	// Parse configuration string
	pairs := strings.Split(configStr, "|")
	for _, pair := range pairs {
		pair = strings.TrimSpace(pair)
		if pair == "" {
			continue
		}

		parts := strings.Split(pair, ":")
		if len(parts) != 2 {
			logger.Printf("Warning: Special API configuration format error: %s (should be BASE_URL_KEY:API_KEY_KEY)", pair)
			continue
		}

		baseUrlKey := strings.TrimSpace(parts[0])
		apiKeyKey := strings.TrimSpace(parts[1])

		if baseUrlKey == "" || apiKeyKey == "" {
			logger.Printf("Warning: Special API configuration empty: %s", pair)
			continue
		}

		specialApiKeys[baseUrlKey] = apiKeyKey
	}

	logger.Printf("Loaded special API configurations: %d", len(specialApiKeys))
	if debugMode {
		for baseUrl, apiKey := range specialApiKeys {
			logger.Printf("  - %s => %s", sanitizeLogString(baseUrl), sanitizeLogString(apiKey))
		}
	}
}

// Main function
func main() {
	// Set service port
	port := getEnvWithDefault("MAGIC_GATEWAY_PORT", "8000")

	// Initialize GPG sign handler (optional feature)
	signHandler, err := handler.NewSignHandler(logger)
	if err != nil {
		logger.Printf("Warning: Failed to initialize GPG sign handler: %v", err)
		logger.Println("GPG signing service will be disabled. To enable it, set a valid AI_DATA_SIGNING_KEY in .env")
	} else {
		// Register signing route (requires authentication)
		http.HandleFunc("/api/ai-generated/sign", withAuth(signHandler.Sign))
		logger.Println("GPG signing service enabled:")
		logger.Println("  - Unified signing at: /api/ai-generated/sign")
	}

	// Initialize user info handler
	userHandler := handler.NewUserHandler(logger)

	// Register user info route (requires authentication)
	http.HandleFunc("/api/user/info", withAuth(userHandler.GetUserInfo))
	logger.Println("User info service enabled:")
	logger.Println("  - User info at: /api/user/info")

	// Register routes
	http.HandleFunc("/auth", authHandler)
	http.HandleFunc("/env", envHandler)
	http.HandleFunc("/status", statusHandler)
	http.HandleFunc("/revoke", revokeHandler)
	http.HandleFunc("/revoke-all", revokeAllTokensHandler) // New endpoint to revoke all tokens
	http.HandleFunc("/services", servicesHandler)
	http.HandleFunc("/", proxyHandler)

	// Start server
	serverAddr := fmt.Sprintf(":%s", port)
	logger.Printf("API Gateway service started at http://localhost%s", serverAddr)
	logger.Fatal(http.ListenAndServe(serverAddr, nil))
}

// Available services handler
func servicesHandler(w http.ResponseWriter, r *http.Request) {
	// Requires authentication
	handler := withAuth(func(w http.ResponseWriter, r *http.Request) {
		// Log complete request info in debug mode
		if debugMode {
			logger.Printf("SERVICES request:")
			logFullRequest(r)
		}

		containerID := r.Header.Get("X-Container-ID")
		magicUserID := r.Header.Get("magic-user-id")
		magicOrganizationCode := r.Header.Get("magic-organization-code")

		// If X-Container-ID is empty but magic-user-id exists, use magic-user-id
		if containerID == "" && magicUserID != "" {
			containerID = magicUserID
		}

		logger.Printf("Services list request from container: %s, user: %s, organization: %s", containerID, magicUserID, magicOrganizationCode)

		// Get available services list
		services := []ServiceInfo{}

		for _, service := range supportedServices {
			baseUrlKey := fmt.Sprintf("%s_API_BASE_URL", service)
			apiKeyExists := false
			modelKey := fmt.Sprintf("%s_MODEL", service)

			baseUrl, hasBaseUrl := envVars[baseUrlKey]

			// Check if API key exists
			apiKeyKey := fmt.Sprintf("%s_API_KEY", service)
			_, apiKeyExists = envVars[apiKeyKey]

			// If both base URL and API key exist, add to service list
			if hasBaseUrl && apiKeyExists {
				// Don't return actual API key, only service info
				serviceInfo := ServiceInfo{
					Name:    service,
					BaseURL: strings.Split(baseUrl, "/")[2], // Only return domain part
				}

				// If default model exists, include it in result
				if model, hasModel := envVars[modelKey]; hasModel {
					serviceInfo.Model = model
				}

				services = append(services, serviceInfo)
			}
		}

		result := map[string]interface{}{
			"available_services": services,
			"message":            "Can use these services via API proxy requests, format: /{service}/path or use env: reference",
		}

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(result)
	})

	handler(w, r)
}

// Authentication handler - Modified for stateless authentication
func authHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Log complete request info in debug mode
	if debugMode {
		logger.Printf("AUTH request:")
		logFullRequest(r)
	}

	// Check if request is from localhost
	clientIP := r.RemoteAddr
	logger.Printf("Authentication request from original address: %s", clientIP)

	// Extract IP address part (remove port)
	// Handle IPv6 and IPv4 formats
	if strings.HasPrefix(clientIP, "[") {
		// IPv6 format: [::1]:12345
		ipEnd := strings.LastIndex(clientIP, "]")
		if ipEnd > 0 {
			clientIP = clientIP[1:ipEnd]
		}
	} else if strings.Contains(clientIP, ":") {
		// IPv4 format: 127.0.0.1:12345
		clientIP = strings.Split(clientIP, ":")[0]
	}

	logger.Printf("Extracted client IP: %s", clientIP)

	// Verify Gateway API Key (use constant-time comparison to prevent timing attacks)
	gatewayAPIKey := r.Header.Get("X-Gateway-API-Key")
	expectedAPIKey := string(jwtSecret) // Use JWT secret directly as expected API key

	if gatewayAPIKey == "" || subtle.ConstantTimeCompare([]byte(gatewayAPIKey), []byte(expectedAPIKey)) != 1 {
		logger.Printf("API key verification failed: provided key does not match or is empty")
		http.Error(w, "Invalid API key", http.StatusUnauthorized)
		return
	}

	// Get user ID
	userID := r.Header.Get("X-USER-ID")
	magicUserID := r.Header.Get("magic-user-id")
	magicOrganizationCode := r.Header.Get("magic-organization-code")
	if userID == "" && magicUserID != "" {
		userID = magicUserID
	}

	if userID == "" {
		userID = "default-user"
	}

	if magicOrganizationCode == "" {
		magicOrganizationCode = ""
	}

	logger.Printf("Authentication request from local user: %s, organization: %s", userID, magicOrganizationCode)

	// Check key rotation
	checkKeyRotation()

	// Generate random number for anti-replay attack
	nonce := generateNonce()

	// Create unique identifier
	tokenID := fmt.Sprintf("%d-%s", time.Now().UnixNano(), userID)

	// Increment token version
	atomic.AddInt64(&tokenVersionCounter, 1)
	currentVersion := atomic.LoadInt64(&tokenVersionCounter)

	// Create JWT claims
	claims := JWTClaims{
		RegisteredClaims: jwt.RegisteredClaims{
			IssuedAt:  jwt.NewNumericDate(time.Now()),
			ID:        tokenID,
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(30 * 24 * time.Hour)), // Expires in 30 days
			NotBefore: jwt.NewNumericDate(time.Now()),                          // Effective immediately
		},
		ContainerID:           userID, // Keep field name unchanged, but store user ID
		MagicUserID:           magicUserID,
		MagicOrganizationCode: magicOrganizationCode,
		TokenVersion:          currentVersion,
		CreatedAt:             time.Now().Unix(),
		KeyID:                 jwtSecretID,
		Nonce:                 nonce,
		Scope:                 "api_gateway", // Define permission scope
	}

	// Create token
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)

	// Set header information
	token.Header["kid"] = jwtSecretID
	token.Header["alg"] = "HS256"
	token.Header["typ"] = "JWT"

	tokenString, err := token.SignedString(jwtSecret)
	if err != nil {
		logger.Printf("Failed to generate token: %v", err)
		http.Error(w, "Failed to generate token", http.StatusInternalServerError)
		return
	}

	logger.Printf("Generated secure token (version: %d, key version: %s) user: %s", currentVersion, jwtSecretID, userID)

	// Return token
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(map[string]string{
		"token":    tokenString,
		"header":   "Magic-Authorization",
		"example":  fmt.Sprintf("Magic-Authorization: Bearer %s", tokenString),
		"note":     "Please ensure to add Bearer prefix when using the token, otherwise the gateway will add it automatically",
		"security": "Token contains replay protection and key version control",
	})
}

// validateToken validates token - modified for stateless validation
func validateToken(tokenString string) (*JWTClaims, bool) {
	// Remove Bearer prefix
	tokenString = strings.TrimPrefix(tokenString, "Bearer ")

	// Parse token, including expiration time validation
	token, err := jwt.ParseWithClaims(tokenString, &JWTClaims{}, func(token *jwt.Token) (interface{}, error) {
		// Validate signing algorithm
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, fmt.Errorf("unexpected signing method: %v", token.Header["alg"])
		}

		// Validate key version
		if kid, ok := token.Header["kid"].(string); ok {
			if kid != jwtSecretID {
				return nil, fmt.Errorf("key version mismatch: %s", kid)
			}
		}

		return jwtSecret, nil
	}) // Now validates standard claims, including expiration time validation

	if err != nil {
		if errors.Is(err, jwt.ErrTokenExpired) {
			logger.Printf("Token expired")
		} else {
			//If debug is enabled, print complete error info
			if debugMode {
				//Print token
				logger.Printf("token: %s", tokenString)
				logger.Printf("Complete error info: %+v", err)
			}
			logger.Printf("Token validation error: %v", err)
		}
		return nil, false
	}

	// Extract claims
	if claims, ok := token.Claims.(*JWTClaims); ok && token.Valid {
		// Check if token was created after global revocation time
		if claims.CreatedAt < atomic.LoadInt64(&globalRevokeTimestamp) {
			logger.Printf("Token has been globally revoked")
			return nil, false
		}

		// Validate permission scope
		if claims.Scope != "api_gateway" {
			logger.Printf("Invalid token permission scope: %s", claims.Scope)
			return nil, false
		}

		// Validate key version
		if claims.KeyID != jwtSecretID {
			logger.Printf("Token key version mismatch: %s", claims.KeyID)
			return nil, false
		}

		return claims, true
	}

	return nil, false
}

// Middleware: validate token
func withAuth(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		// Get token (prioritize Magic-Authorization header, then standard Authorization header)
		authHeader := r.Header.Get("Magic-Authorization")
		if authHeader == "" {
			// If Magic-Authorization doesn't exist, try standard Authorization header
			authHeader = r.Header.Get("Authorization")
			if authHeader == "" {
				http.Error(w, "Authorization required", http.StatusUnauthorized)
				return
			}

			// Check if standard Authorization header contains Bearer prefix
			if !strings.HasPrefix(strings.ToLower(authHeader), "bearer ") {
				// If no Bearer prefix, add it automatically
				authHeader = "Bearer " + authHeader
				if debugMode {
					logger.Printf("Automatically added Bearer prefix to Authorization header: %s", authHeader)
				}
			}
		} else {
			// Check if Magic-Authorization header contains Bearer prefix
			if !strings.HasPrefix(strings.ToLower(authHeader), "bearer ") {
				// If no Bearer prefix, add it automatically
				authHeader = "Bearer " + authHeader
				if debugMode {
					//logger.Printf("Automatically added Bearer prefix to Magic-Authorization header: %s", authHeader)
				}
			}
		}

		// Validate token
		claims, valid := validateToken(authHeader)
		if !valid {
			http.Error(w, "Invalid or expired token", http.StatusUnauthorized)
			return
		}

		// Store token information in request context
		r.Header.Set("X-User-Id", claims.ContainerID)
		r.Header.Set("magic-user-id", claims.MagicUserID)
		r.Header.Set("magic-organization-code", claims.MagicOrganizationCode)

		// Store JWT claims in request context for subsequent handlers
		ctx := context.WithValue(r.Context(), "jwt_claims", claims)
		r = r.WithContext(ctx)

		// Call next handler
		next(w, r)
	}
}

// Environment variable handler
func envHandler(w http.ResponseWriter, r *http.Request) {
	// Requires authentication
	handler := withAuth(func(w http.ResponseWriter, r *http.Request) {
		// Log full request info in debug mode
		if debugMode {
			logger.Printf("ENV request:")
			logFullRequest(r)
		}

		// Get requested environment variables
		varsParam := r.URL.Query().Get("vars")
		userID := r.Header.Get("X-USER-ID")
		magicUserID := r.Header.Get("magic-user-id")
		magicOrganizationCode := r.Header.Get("magic-organization-code")

		// If X-USER-ID is empty but magic-user-id exists, use magic-user-id
		if userID == "" && magicUserID != "" {
			userID = magicUserID
		}

		logger.Printf("Environment variable request from user %s, organization: %s, variables: %s", userID, magicOrganizationCode, varsParam)

		// No longer return actual environment variable values, but return list of available environment variable names
		allowedVarNames := getAvailableEnvVarNames()

		// If specific variables requested, only return existence status of these variables in available list
		var result map[string]interface{}

		if varsParam == "" {
			// Return all available environment variable names
			result = map[string]interface{}{
				"available_vars": allowedVarNames,
				"message":        "Direct access to environment variable values not allowed, please use these variables through API proxy requests",
			}
		} else {
			// Return whether requested specific variables are available
			requestedVars := strings.Split(varsParam, ",")
			availableMap := make(map[string]bool)

			for _, varName := range requestedVars {
				varName = strings.TrimSpace(varName)
				// Check if variable is in available list
				found := false
				for _, allowedVar := range allowedVarNames {
					if varName == allowedVar {
						found = true
						break
					}
				}
				availableMap[varName] = found
			}

			result = map[string]interface{}{
				"available_status": availableMap,
				"message":          "Direct access to environment variable values not allowed, please use these variables through API proxy requests",
			}
		}

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(result)
	})

	handler(w, r)
}

// getEnvVarBlacklist returns blacklist patterns from environment variable or default values
func getEnvVarBlacklist() []string {
	blacklistStr := getEnvWithDefault("MAGIC_GATEWAY_ENV_BLACKLIST", "")
	if blacklistStr == "" {
		// Default blacklist patterns
		return []string{
			"MAGIC_GATEWAY_API_KEY",
			"JWT_SECRET",
			"SECRET",
			"PASSWORD",
			"PRIVATE_KEY",
			"CREDENTIAL",
		}
	}
	return strings.Split(blacklistStr, ",")
}

// getEnvVarWhitelistPrefixes returns whitelist prefixes from environment variable
func getEnvVarWhitelistPrefixes() []string {
	whitelistStr := getEnvWithDefault("MAGIC_GATEWAY_ENV_WHITELIST_PREFIXES", "")
	if whitelistStr == "" {
		logger.Printf("Warning: MAGIC_GATEWAY_ENV_WHITELIST_PREFIXES not configured, environment variable access will be restricted")
		return []string{}
	}

	// Remove surrounding quotes if they exist
	whitelistStr = strings.Trim(whitelistStr, `"'`)

	prefixes := strings.Split(whitelistStr, ",")
	result := make([]string, 0, len(prefixes))

	// Trim spaces and quotes, filter empty strings
	for _, prefix := range prefixes {
		prefix = strings.TrimSpace(prefix)
		prefix = strings.Trim(prefix, `"'`)
		if prefix != "" {
			result = append(result, prefix)
		}
	}

	if len(result) == 0 {
		logger.Printf("Warning: MAGIC_GATEWAY_ENV_WHITELIST_PREFIXES configuration is empty or invalid")
	}

	return result
}

// isEnvVarAllowed checks if access to an environment variable is allowed
func isEnvVarAllowed(varName string) bool {
	// First check blacklist
	blacklist := getEnvVarBlacklist()
	varNameUpper := strings.ToUpper(varName)
	for _, blocked := range blacklist {
		if strings.Contains(varNameUpper, strings.ToUpper(blocked)) {
			return false
		}
	}

	// Check whitelist prefixes
	allowedPrefixes := getEnvVarWhitelistPrefixes()
	if len(allowedPrefixes) == 0 {
		// Whitelist not configured, deny access
		return false
	}

	for _, prefix := range allowedPrefixes {
		if strings.HasPrefix(varName, prefix) {
			return true
		}
	}

	return false
}

// getAvailableEnvVarNames gets available environment variable names
func getAvailableEnvVarNames() []string {
	allowedVarNames := []string{}

	for key := range envVars {
		if isEnvVarAllowed(key) {
			allowedVarNames = append(allowedVarNames, key)
		}
	}

	return allowedVarNames
}

// statusHandler status handler - modified for stateless authentication
func statusHandler(w http.ResponseWriter, r *http.Request) {
	// Log full request info in debug mode
	if debugMode {
		logger.Printf("STATUS request:")
		logFullRequest(r)
	}

	// Get available environment variable names
	allowedVarNames := getAvailableEnvVarNames()

	// Get available services
	availableServices := []string{}
	for _, service := range supportedServices {
		baseUrlKey := fmt.Sprintf("%s_API_BASE_URL", service)
		apiKeyKey := fmt.Sprintf("%s_API_KEY", service)

		if _, hasBaseUrl := envVars[baseUrlKey]; hasBaseUrl {
			if _, hasApiKey := envVars[apiKeyKey]; hasApiKey {
				availableServices = append(availableServices, service)
			}
		}
	}

	// Return status information
	status := map[string]interface{}{
		"status":                  "ok",
		"version":                 getEnvWithDefault("API_GATEWAY_VERSION", "1.0.0"),
		"auth_mode":               "stateless_jwt",
		"token_validity":          "30 days",
		"env_vars_available":      allowedVarNames,
		"services_available":      availableServices,
		"current_token_version":   atomic.LoadInt64(&tokenVersionCounter),
		"global_revoke_timestamp": atomic.LoadInt64(&globalRevokeTimestamp),
		"jwt_key_id":              jwtSecretID,
		"jwt_algorithm":           "HS256",
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(status)
}

// revokeHandler token revocation handler - modified for version-based revocation
func revokeHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Requires authentication
	handler := withAuth(func(w http.ResponseWriter, r *http.Request) {
		// Log full request info in debug mode
		if debugMode {
			logger.Printf("REVOKE request:")
			logFullRequest(r)
		}

		// Parse request body
		var requestBody struct {
			TokenID string `json:"token_id"`
		}

		err := json.NewDecoder(r.Body).Decode(&requestBody)
		if err != nil {
			http.Error(w, "Invalid request body", http.StatusBadRequest)
			return
		}

		// For stateless authentication, we cannot revoke individual tokens
		// But we can set a global revocation timestamp to revoke all tokens
		logger.Printf("Individual token revocation request: %s (stateless authentication does not support individual revocation)", requestBody.TokenID)

		// Return success
		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]interface{}{
			"success": true,
			"message": "In stateless authentication mode, please use /revoke-all endpoint to revoke all tokens",
		})
	})

	handler(w, r)
}

// revokeAllTokensHandler revoke all tokens handler
func revokeAllTokensHandler(w http.ResponseWriter, r *http.Request) {
	if r.Method != http.MethodPost {
		http.Error(w, "Method not allowed", http.StatusMethodNotAllowed)
		return
	}

	// Requires authentication
	handler := withAuth(func(w http.ResponseWriter, r *http.Request) {
		// Log full request info in debug mode
		if debugMode {
			logger.Printf("REVOKE_ALL request:")
			logFullRequest(r)
		}

		// Set global revocation timestamp to current time
		atomic.StoreInt64(&globalRevokeTimestamp, time.Now().Unix())

		logger.Printf("All tokens revoked")

		w.Header().Set("Content-Type", "application/json")
		json.NewEncoder(w).Encode(map[string]interface{}{
			"success":          true,
			"message":          "All tokens have been revoked",
			"revoke_timestamp": atomic.LoadInt64(&globalRevokeTimestamp),
		})
	})

	handler(w, r)
}

// cleanupExpiredTokens cleanup expired tokens - not needed for stateless authentication
func cleanupExpiredTokens() {
	// In stateless authentication mode, JWT automatically handles expiration
	// No manual cleanup needed
}

// getServiceInfo gets service information
func getServiceInfo(service string) (string, string, bool) {
	baseUrlKey := fmt.Sprintf("%s_API_BASE_URL", strings.ToUpper(service))
	apiKeyKey := fmt.Sprintf("%s_API_KEY", strings.ToUpper(service))

	baseUrl, baseUrlExists := envVars[baseUrlKey]
	apiKey, apiKeyExists := envVars[apiKeyKey]

	if baseUrlExists && apiKeyExists {
		return baseUrl, apiKey, true
	}

	return "", "", false
}

// API proxy handler
func proxyHandler(w http.ResponseWriter, r *http.Request) {
	// Exclude specific endpoints
	path := strings.Trim(r.URL.Path, "/")
	if path == "auth" || path == "env" || path == "status" || path == "revoke" || path == "revoke-all" || path == "services" {
		http.Error(w, "Invalid endpoint", http.StatusNotFound)
		return
	}

	// Requires authentication
	handler := withAuth(func(w http.ResponseWriter, r *http.Request) {
		// Get user info from JWT claims
		userID := r.Header.Get("X-USER-ID")
		// Get magic-task-id and magic-topic-id from request headers
		magicTaskID := r.Header.Get("magic-task-id")
		magicTopicID := r.Header.Get("magic-topic-id")
		magicChatTopicID := r.Header.Get("magic-chat-topic-id")
		magicLanguage := r.Header.Get("magic-language")

		// Prioritize getting from original request headers to avoid JWT override
		magicUserID := r.Header.Get("magic-user-id")
		magicOrganizationCode := r.Header.Get("magic-organization-code")

		// Get JWT claims from request context as fallback
		if claims, ok := r.Context().Value("jwt_claims").(*JWTClaims); ok {
			// Only use JWT values when original request headers don't have values
			if magicUserID == "" {
				magicUserID = claims.MagicUserID
			}
			if magicOrganizationCode == "" {
				magicOrganizationCode = claims.MagicOrganizationCode
			}
		}

		if userID != "" {
			magicUserID = userID
		}

		if debugMode {
			logger.Printf("Original request header magic-user-id: %s", r.Header.Get("magic-user-id"))
			logger.Printf("Original request header magic-organization-code: %s", r.Header.Get("magic-organization-code"))
			logger.Printf("Final magicUserID used: %s", magicUserID)
			logger.Printf("Final magicOrganizationCode used: %s", magicOrganizationCode)
		}

		logger.Printf("Proxy request from user: %s, organization: %s, path: %s",
			sanitizeLogString(userID),
			sanitizeLogString(magicOrganizationCode),
			sanitizeLogString(path))

		// Log full request details in debug mode
		if debugMode {
			logger.Printf("PROXY request:")
			logFullRequest(r)
		}

		// Limit request body size to avoid DoS
		r.Body = http.MaxBytesReader(w, r.Body, maxRequestBodySize)

		// Read request body
		bodyBytes, err := io.ReadAll(r.Body)
		if err != nil {
			if err.Error() == "http: request body too large" {
				http.Error(w, "Request body too large, max 10MB", http.StatusRequestEntityTooLarge)
			} else {
				http.Error(w, "Failed to read request body", http.StatusInternalServerError)
			}
			return
		}
		r.Body.Close()

		// Determine target service URL (decide target before processing body)
		targetBase := r.URL.Query().Get("target")

		var targetApiKey string
		shouldAddApiKey := false

		// 0. Check if an env var name is used as the URL path prefix
		if targetBase == "" {
			pathParts := strings.SplitN(path, "/", 2)
			envVarName := pathParts[0]
			remainingPath := ""
			if len(pathParts) > 1 {
				remainingPath = pathParts[1]
			}

			// Validate whether the env var name is allowed
			if !isEnvVarAllowed(envVarName) {
				logger.Printf("Denied access to unauthorized environment variable: %s", sanitizeLogString(envVarName))
				http.Error(w, "Not Found", http.StatusNotFound)
				return
			}

			// Check if this matches an environment variable name
			if envVarValue, exists := envVars[envVarName]; exists {
				targetBase = envVarValue
				path = remainingPath
				logger.Printf("Access via environment variable name: %s", sanitizeLogString(envVarName))

				// If API_BASE_URL type, try to find a matching API_KEY
				if strings.HasSuffix(envVarName, "_API_BASE_URL") {
					servicePrefix := strings.TrimSuffix(envVarName, "_API_BASE_URL")
					apiKeyVarName := servicePrefix + "_API_KEY"

					if apiKey, exists := envVars[apiKeyVarName]; exists {
						targetApiKey = apiKey
						shouldAddApiKey = true
						logger.Printf("Found matching API key: %s", sanitizeLogString(apiKeyVarName))
					}
				}
			}
		}

		// 1. Check the direct service path pattern "/service/path"
		if targetBase == "" && strings.Contains(path, "/") {
			parts := strings.SplitN(path, "/", 2)
			serviceName := strings.ToUpper(parts[0])

			// Check if the service is supported
			for _, supportedService := range supportedServices {
				if serviceName == supportedService {
					if baseUrl, apiKey, found := getServiceInfo(serviceName); found {
						targetBase = baseUrl
						targetApiKey = apiKey
						path = parts[1]
						shouldAddApiKey = true
						logger.Printf("Direct service path request: %s => %s", serviceName, targetBase)
						break
					}
				}
			}
		}

		// 2. If not found via service path, try query parameter
		if targetBase == "" {
			serviceName := r.URL.Query().Get("service")
			if serviceName != "" {
				if baseUrl, apiKey, found := getServiceInfo(serviceName); found {
					targetBase = baseUrl
					targetApiKey = apiKey
					shouldAddApiKey = true
					logger.Printf("Request service via query parameter: %s => %s", serviceName, targetBase)
				}
			}
		}

		// 3. If still not found, extract service name from path for env lookup
		if targetBase == "" && strings.Contains(path, "/") {
			serviceName := strings.SplitN(path, "/", 2)[0]
			envVarName := fmt.Sprintf("%s_API_URL", strings.ToUpper(serviceName))
			if envValue, exists := envVars[envVarName]; exists {
				targetBase = envValue
				path = strings.SplitN(path, "/", 2)[1]
				logger.Printf("Get target URL from environment variable: %s=%s", envVarName, targetBase)

				// Attempt to get the corresponding API key
				apiKeyVarName := fmt.Sprintf("%s_API_KEY", strings.ToUpper(serviceName))
				if apiKey, exists := envVars[apiKeyVarName]; exists {
					targetApiKey = apiKey
					shouldAddApiKey = true
				}
			}
		}

		// Replace env vars in URL (must run before validation)
		targetBase = replaceEnvVarsInString(targetBase)

		// Validate target URL against allowlist with detailed errors
		if err := validateTargetURL(targetBase); err != nil {
			logger.Printf("Denied access to unauthorized target URL: %s, reason: %v", targetBase, err)
			http.Error(w, "Not Found", http.StatusNotFound)
			return
		}
		logger.Printf("Target URL validated: %s", targetBase)

		// Check whether this should use Volcengine APM
		if ShouldUseVolcengineAPMHandler(targetBase) {
			endpoint, appKey, ok := GetVolcengineAPMConfig()
			if !ok {
				logger.Printf("Warning: Volcengine APM request detected but VOLCENGINE_APM_APPKEY not configured")
				// Continue using regular HTTP proxying
			} else {
				logger.Printf("Using Volcengine APM gRPC handler")
				handler := NewVolcengineAPMHandler(endpoint, appKey)

				// Build the full URL for logging
				targetBase = strings.TrimSuffix(targetBase, "/")
				path = strings.TrimPrefix(path, "/")
				fullTargetURL := fmt.Sprintf("%s/%s", targetBase, path)

				if err := handler.HandleVolcengineAPMRequest(w, r, fullTargetURL, bodyBytes); err != nil {
					logger.Printf("Volcengine APM request failed: %v", err)
					http.Error(w, fmt.Sprintf("Failed to handle request: %v", err), http.StatusBadGateway)
				}
				return
			}
		}

		// Check for special APIs and only replace body for those
		isSpecialAPI := false
		for baseUrlKey := range specialApiKeys {
			if baseUrlValue, exists := envVars[baseUrlKey]; exists {
				if strings.HasPrefix(targetBase, baseUrlValue) {
					remainingPart := targetBase[len(baseUrlValue):]
					if remainingPart == "" || strings.HasPrefix(remainingPart, "/") {
						isSpecialAPI = true
						break
					}
				}
			}
		}

		// Handle JSON request - only replace for special APIs
		contentType := r.Header.Get("Content-Type")
		var jsonData interface{}
		if isSpecialAPI && strings.Contains(contentType, "application/json") {
			var data interface{}
			if err := json.Unmarshal(bodyBytes, &data); err == nil {
				jsonData = data
			} else {
				logger.Printf("Failed to parse JSON request body: %v", err)
			}
		}

		// Create new request body (use original when no replacement needed)
		if jsonData == nil {
			r.Body = io.NopCloser(bytes.NewBuffer(bodyBytes))
		}

		// Build request headers and replace env references
		proxyHeaders := make(http.Header)
		for key, values := range r.Header {
			if shouldSkipHeader(key) {
				continue
			}

			for _, value := range values {

				// Special handling for X-ByteAPM-AppKey=
				if strings.Contains(value, "X-ByteAPM-AppKey=") {
					if otelHeaders, exists := envVars["OTEL_EXPORTER_OTLP_HEADERS"]; exists {
						// Use precompiled regex to replace X-ByteAPM-AppKey=xxx with the actual value
						newValue := byteAPMAppKeyRegex.ReplaceAllString(value, otelHeaders)
						proxyHeaders.Add(key, newValue)
						if debugMode {
							logger.Printf("Replace OTEL ByteAPM AppKey format: %s: %s => %s", key, value, newValue)
						}
						continue
					}
				}

				// Special handling for Authorization header
				if key == "Authorization" {
					// Handle Bearer env:XXX pattern
					if strings.HasPrefix(value, "Bearer env:") {
						envKey := strings.TrimPrefix(value, "Bearer env:")
						if envValue, exists := envVars[envKey]; exists {
							proxyHeaders.Add(key, "Bearer "+envValue)
							if debugMode {
								logger.Printf("Replace env reference (Bearer env:): %s", sanitizeLogString(envKey))
							}
							continue
						}
					}

					// Handle direct env var names like Bearer OPENAI_API_KEY
					if strings.HasPrefix(value, "Bearer ") {
						tokenValue := strings.TrimPrefix(value, "Bearer ")
						if envValue, exists := envVars[tokenValue]; exists {
							proxyHeaders.Add(key, "Bearer "+envValue)
							if debugMode {
								logger.Printf("Replace env reference (direct): Bearer %s", sanitizeLogString(tokenValue))
							}
							continue
						}
					}
				}

				// Check whether header values are environment variable names
				if envValue, exists := envVars[value]; exists {
					// If header value fully equals an env var name, replace with its value
					proxyHeaders.Add(key, envValue)
					if debugMode {
						logger.Printf("Replace env var name in request header: %s: %s", sanitizeLogString(key), sanitizeLogString(value))
					}
					continue
				}

				// Replace env references inside header values
				newValue := replaceEnvVarsInString(value)
				proxyHeaders.Add(key, newValue)
				if debugMode && newValue != value {
					logger.Printf("Replace env reference in request header: %s: %s", sanitizeLogString(key), sanitizeLogString(value))
				}
			}
		}

		// Handle API key replacement inside JSON bodies for special APIs
		if jsonData != nil {
			jsonData = processApiKeyInBody(jsonData, targetBase)
			// Re-serialize JSON data
			if newBody, err := json.Marshal(jsonData); err == nil {
				bodyBytes = newBody
				// Update request body
				r.Body = io.NopCloser(bytes.NewBuffer(bodyBytes))
			}
		}

		// Build the full URL
		targetBase = strings.TrimSuffix(targetBase, "/")
		path = strings.TrimPrefix(path, "/")
		targetURL := fmt.Sprintf("%s/%s", targetBase, path)

		// Handle URL query parameters
		if r.URL.RawQuery != "" {
			// Handle env vars in query parameters
			queryValues := r.URL.Query()
			hasChanges := false

			for key, values := range queryValues {
				for i, value := range values {
					// Check whether this is an env var name
					if envValue, exists := envVars[value]; exists {
						queryValues.Set(key, envValue)
						hasChanges = true
						if debugMode {
							logger.Printf("Replace env var name in URL param: %s=%s", sanitizeLogString(key), sanitizeLogString(value))
						}
					} else {
						// Replace env references within parameter values
						newValue := replaceEnvVarsInString(value)
						if newValue != value {
							values[i] = newValue
							hasChanges = true
							if debugMode {
								logger.Printf("Replace env reference in URL param: %s=%s", sanitizeLogString(key), sanitizeLogString(value))
							}
						}
					}
				}

				// Update query params
				if hasChanges {
					queryValues[key] = values
				}
			}

			// Rebuild query string
			if hasChanges {
				targetURL = fmt.Sprintf("%s?%s", targetURL, queryValues.Encode())
			} else {
				targetURL = fmt.Sprintf("%s?%s", targetURL, r.URL.RawQuery)
			}
		}

		logger.Printf("Forwarding request to: %s", targetURL)

		// Create proxy request
		proxyReq, err := http.NewRequest(r.Method, targetURL, r.Body)
		if err != nil {
			http.Error(w, "Failed to create proxy request", http.StatusInternalServerError)
			return
		}

		// Set request headers
		proxyReq.Header = proxyHeaders

		// Pass through magic-user-id and magic-organization-code to the target API
		// Only set from JWT when the original headers do not include values
		if proxyReq.Header.Get("magic-user-id") == "" && magicUserID != "" {
			proxyReq.Header.Set("magic-user-id", magicUserID)
			if debugMode {
				logger.Printf("Set magic-user-id from JWT: %s", magicUserID)
			}
		} else if debugMode && proxyReq.Header.Get("magic-user-id") != "" {
			logger.Printf("Preserved original magic-user-id: %s", proxyReq.Header.Get("magic-user-id"))
		}

		if proxyReq.Header.Get("magic-organization-code") == "" && magicOrganizationCode != "" {
			proxyReq.Header.Set("magic-organization-code", magicOrganizationCode)
			if debugMode {
				logger.Printf("Set magic-organization-code from JWT: %s", magicOrganizationCode)
			}
		} else if debugMode && proxyReq.Header.Get("magic-organization-code") != "" {
			logger.Printf("Preserved original magic-organization-code: %s", proxyReq.Header.Get("magic-organization-code"))
		}

		if magicTaskID != "" {
			proxyReq.Header.Set("magic-task-id", magicTaskID)
			if debugMode {
				logger.Printf("Forward magic-task-id: %s", magicTaskID)
			}
		}

		if magicTopicID != "" {
			proxyReq.Header.Set("magic-topic-id", magicTopicID)
			if debugMode {
				logger.Printf("Forward magic-topic-id: %s", magicTopicID)
			}
		}

		if magicChatTopicID != "" {
			proxyReq.Header.Set("magic-chat-topic-id", magicChatTopicID)
			if debugMode {
				logger.Printf("Forward magic-chat-topic-id: %s", magicChatTopicID)
			}
		}

		if magicLanguage != "" {
			proxyReq.Header.Set("magic-language", magicLanguage)
			if debugMode {
				logger.Printf("Forward magic-language: %s", magicLanguage)
			}
		}

		// Add API key when needed and Authorization is missing
		if shouldAddApiKey && !headerExists(proxyHeaders, "Authorization") {
			proxyReq.Header.Set("Authorization", "Bearer "+targetApiKey)
			logger.Printf("Added target service API key")
		}

		// Send request with 2-minute timeout to prevent resource exhaustion
		// Disable automatic redirects to avoid bypassing the allowlist
		client := &http.Client{
			Timeout: 2 * time.Minute,
			CheckRedirect: func(req *http.Request, via []*http.Request) error {
				// Validate redirect URL against the allowlist
				redirectURL := req.URL.String()
				if err := validateTargetURL(redirectURL); err != nil {
					logger.Printf("Blocked redirect to unauthorized URL: %s, reason: %v", redirectURL, err)
					return fmt.Errorf("Redirect target URL is not allowlisted")
				}
				// Limit redirect count to prevent loops
				if len(via) >= 5 {
					return fmt.Errorf("Redirect count exceeded limit")
				}
				logger.Printf("Allowed redirect to: %s", redirectURL)
				return nil
			},
		}
		resp, err := client.Do(proxyReq)
		if err != nil {
			logger.Printf("Proxy error: %v", err)
			http.Error(w, fmt.Sprintf("Proxy error: %v", err), http.StatusBadGateway)
			return
		}
		defer resp.Body.Close()

		logger.Printf("Proxy response status code: %d", resp.StatusCode)

		// Check for SSE streaming responses
		contentType = resp.Header.Get("Content-Type")
		isSSEResponse := strings.Contains(strings.ToLower(contentType), "text/event-stream") ||
			strings.Contains(strings.ToLower(contentType), "text/stream") ||
			strings.Contains(strings.ToLower(contentType), "application/stream")

		// Set response headers
		for key, values := range resp.Header {
			if !shouldSkipHeader(key) {
				for _, value := range values {
					w.Header().Add(key, value)
				}
			}
		}

		// Set status code
		w.WriteHeader(resp.StatusCode)

		// Handle SSE streaming responses
		if isSSEResponse {
			logger.Printf("Detected SSE streaming response, start streaming")

			// Ensure the connection stays active and stream headers are set
			if flusher, ok := w.(http.Flusher); ok {
				// Stream data to the client in real time
				buffer := make([]byte, 4096)
				for {
					n, err := resp.Body.Read(buffer)
					if n > 0 {
						// Write data to client
						w.Write(buffer[:n])
						// Flush immediately to ensure real-time delivery
						flusher.Flush()

						if debugMode {
							logger.Printf("Forwarded SSE chunk: %d bytes", n)
						}
					}
					if err != nil {
						if err == io.EOF {
							logger.Printf("SSE stream ended")
						} else {
							logger.Printf("Error reading SSE stream: %v", err)
						}
						break
					}
				}
			} else {
				logger.Printf("Warning: ResponseWriter does not support Flush, falling back to buffered response")
				// Fallback: read the full response body
				respBody, err := io.ReadAll(resp.Body)
				if err != nil {
					logger.Printf("Failed to read response body: %v", err)
					return
				}
				w.Write(respBody)
			}
		} else {
			// Non-stream response handling
			// Log full response details in debug mode
			if debugMode {
				logFullResponse(resp, targetURL)
			}

			// Limit response size to prevent memory exhaustion
			limitedReader := io.LimitReader(resp.Body, maxResponseBodySize)
			respBody, err := io.ReadAll(limitedReader)
			if err != nil {
				logger.Printf("Failed to read response body: %v", err)
				http.Error(w, "Failed to read response body", http.StatusInternalServerError)
				return
			}

			// Check whether the response was truncated by size limits
			if int64(len(respBody)) >= maxResponseBodySize {
				logger.Printf("Warning: response body may exceed 100MB limit and was truncated")
			}

			// Only log response body in debug mode to avoid leaking sensitive data
			if debugMode {
				if len(respBody) > 100*1024 {
					logger.Printf("Response body larger than 100kb, not printing")
				} else {
					logger.Printf("Response body: %s", string(respBody))
				}
			}

			// Forward response body
			w.Write(respBody)
		}
	})

	handler(w, r)
}

// Check whether a request header should be skipped
func shouldSkipHeader(key string) bool {
	key = strings.ToLower(key)
	// Keep more headers for streaming responses
	skipHeaders := []string{"host", "x-forwarded-for"}

	// For streaming responses, do not skip connection-related headers
	// content-length is usually unnecessary or handled automatically
	for _, h := range skipHeaders {
		if key == h {
			return true
		}
	}
	return false
}

// Check if a request header exists
func headerExists(headers http.Header, key string) bool {
	_, ok := headers[key]
	return ok
}

// Recursively replace env var references within a data structure
func replaceEnvVars(data interface{}) interface{} {
	switch v := data.(type) {
	case map[string]interface{}:
		result := make(map[string]interface{})
		for key, value := range v {
			result[key] = replaceEnvVars(value)
		}
		return result

	case []interface{}:
		result := make([]interface{}, len(v))
		for i, item := range v {
			result[i] = replaceEnvVars(item)
		}
		return result

	case string:
		originalValue := v

		// Check for env: prefix
		if strings.HasPrefix(v, "env:") {
			envKey := strings.TrimPrefix(v, "env:")
			if value, exists := envVars[envKey]; exists {
				if debugMode {
					logger.Printf("Environment variable replacement: env:%s => %s", sanitizeLogString(envKey), maskSensitiveValue(value))
				}
				return value
			}
			return v
		}

		// Directly check if this is an env var name (supports all vars defined in .env)
		if value, exists := envVars[v]; exists {
			// Only replace when the string fully equals the env var name
			if debugMode {
				logger.Printf("Environment variable name replacement: %s => %s", sanitizeLogString(v), maskSensitiveValue(value))
			}
			return value
		}

		// Replace other env var reference formats
		newValue := replaceEnvVarsInString(v)
		if newValue != originalValue && debugMode {
			logger.Printf("String env variable replacement: %s => %s", sanitizeLogString(originalValue), maskSensitiveValue(newValue))
		}
		return newValue

	default:
		return v
	}
}

// Replace env var references inside a string
func replaceEnvVarsInString(s string) string {
	// Replace ${VAR}
	re1 := regexp.MustCompile(`\${([A-Za-z0-9_]+)}`)
	s = re1.ReplaceAllStringFunc(s, func(match string) string {
		varName := re1.FindStringSubmatch(match)[1]
		if value, exists := envVars[varName]; exists {
			return value
		}
		return match
	})

	// Replace $VAR
	re2 := regexp.MustCompile(`\$([A-Za-z0-9_]+)`)
	s = re2.ReplaceAllStringFunc(s, func(match string) string {
		varName := re2.FindStringSubmatch(match)[1]
		if value, exists := envVars[varName]; exists {
			return value
		}
		return match
	})

	// Replace {$VAR}
	re3 := regexp.MustCompile(`\{\$([A-Za-z0-9_]+)\}`)
	s = re3.ReplaceAllStringFunc(s, func(match string) string {
		varName := re3.FindStringSubmatch(match)[1]
		if value, exists := envVars[varName]; exists {
			return value
		}
		return match
	})

	return s
}

	// Log full response details
func logFullResponse(resp *http.Response, targetURL string) {
	logger.Printf("======= Debug mode - full response =======")
	logger.Printf("Target URL: %s", targetURL)
	logger.Printf("Response status: %s", resp.Status)
	logger.Printf("Response protocol: %s", resp.Proto)

	// Log all response headers
	logger.Printf("--- Response headers ---")
	for key, values := range resp.Header {
		for _, value := range values {
			logger.Printf("%s: %s", key, value)
		}
	}

	// Read and log response body then reset
	logger.Printf("--- Response body ---")
	bodyBytes, err := io.ReadAll(resp.Body)
	if err != nil {
		logger.Printf("Failed to read response body: %v", err)
	} else {
		// Try to pretty-print JSON bodies for readability
		contentType := resp.Header.Get("Content-Type")
		if strings.Contains(contentType, "application/json") {
			var prettyJSON bytes.Buffer
			err = json.Indent(&prettyJSON, bodyBytes, "", "  ")
			if err == nil {
				logger.Printf("%s", prettyJSON.String())
			} else {
				logger.Printf("%s", string(bodyBytes))
			}
		} else {
			logger.Printf("%s", string(bodyBytes))
		}

		// Reset response body for downstream handling
		resp.Body = io.NopCloser(bytes.NewBuffer(bodyBytes))
	}
	logger.Printf("====================================")
}

	// Log request details
func logFullRequest(r *http.Request) {
	logger.Printf("======= Debug mode - full request =======")
	logger.Printf("Request method: %s", r.Method)
	logger.Printf("Full URL: %s", r.URL.String())
	logger.Printf("Request protocol: %s", r.Proto)
	logger.Printf("Remote address: %s", r.RemoteAddr)

	// Log all request headers
	logger.Printf("--- Request headers ---")
	for key, values := range r.Header {
		for _, value := range values {
			if debugMode {
				// Filter out Magic-Authorization and X-Gateway-Api-Key
				if key != "Magic-Authorization" && key != "X-Gateway-Api-Key" {
					logger.Printf("%s: %s", key, value)
				}
			}
		}
	}

	// Read and log request body, then reset
	logger.Printf("--- Request body ---")
	bodyBytes, err := io.ReadAll(r.Body)
	if err != nil {
		logger.Printf("Failed to read request body: %v", err)
	} else {
		// Try to pretty-print JSON request bodies
		// contentType := r.Header.Get("Content-Type")
		// if strings.Contains(contentType, "application/json") {
		// 	var prettyJSON bytes.Buffer
		// 	err = json.Indent(&prettyJSON, bodyBytes, "", "  ")
		// 	if err == nil {
		// 		logger.Printf("%s", prettyJSON.String())
		// 	} else {
		// 		if debugMode{
		// 			logger.Printf("%s", string(bodyBytes))
		// 		}
		// 	}
		// } else {
		// 	if debugMode{
		// 		logger.Printf("%s", string(bodyBytes))
		// 	}
		// }

		// Reset request body for downstream handling
		r.Body = io.NopCloser(bytes.NewBuffer(bodyBytes))
	}
	logger.Printf("=====================================")
}

// processApiKeyInBody replaces API keys in request bodies for special API_BASE_URL entries
func processApiKeyInBody(data interface{}, targetBase string) interface{} {
	// Check whether the target URL matches any configured API_BASE_URL
	var matchedApiKey string
	for baseUrlKey, apiKeyKey := range specialApiKeys {
		if baseUrlValue, exists := envVars[baseUrlKey]; exists {
			// Verify target URL strictly matches the API_BASE_URL value
			if strings.HasPrefix(targetBase, baseUrlValue) {
				// Ensure the next character is a path separator or URL end
				remainingPart := targetBase[len(baseUrlValue):]
				if remainingPart == "" || strings.HasPrefix(remainingPart, "/") {
					if apiKeyValue, exists := envVars[apiKeyKey]; exists {
						matchedApiKey = apiKeyValue
						if debugMode {
							logger.Printf("Detected special API_BASE_URL match: %s => %s", baseUrlKey, apiKeyKey)
						}
						break
					}
				}
			}
		}
	}

	// No match found; return original data
	if matchedApiKey == "" {
		return data
	}

	// Recursively replace API keys within the data structure
	return replaceApiKeyInData(data, matchedApiKey)
}

// replaceApiKeyInData recursively replaces API keys within nested data
func replaceApiKeyInData(data interface{}, apiKey string) interface{} {
	switch v := data.(type) {
	case map[string]interface{}:
		result := make(map[string]interface{})
		for key, value := range v {
			// Detect API-key-related fields
			if isApiKeyField(key) {
				// Replace empty or placeholder values with the real API key
				if strValue, ok := value.(string); ok {
					// Check common placeholder formats
					if strValue == "" ||
						strValue == "env:"+key ||
						strValue == "${"+key+"}" ||
						strValue == "$"+key ||
						strValue == "{$"+key+"}" ||
						strings.Contains(strValue, "${") ||
						strings.Contains(strValue, "$") {
						result[key] = apiKey
						if debugMode {
							logger.Printf("Replaced API key placeholder in request body: %s", sanitizeLogString(key))
						}
						continue
					}
				}
			}
			result[key] = replaceApiKeyInData(value, apiKey)
		}
		return result

	case []interface{}:
		result := make([]interface{}, len(v))
		for i, item := range v {
			result[i] = replaceApiKeyInData(item, apiKey)
		}
		return result

	default:
		return v
	}
}

// isApiKeyField checks whether a field name is related to API keys
func isApiKeyField(fieldName string) bool {
	// Common API key field names
	baseApiKeyFields := []string{
		"api_key", "apiKey", "access_key", "accessKey", "key", "token", "authorization",
	}

	// Collect API key env var names from special API config
	dynamicApiKeyFields := make([]string, 0, len(specialApiKeys))
	for _, apiKeyEnvName := range specialApiKeys {
		dynamicApiKeyFields = append(dynamicApiKeyFields, apiKeyEnvName)
	}

	fieldNameLower := strings.ToLower(fieldName)

	// Check common fields
	for _, apiField := range baseApiKeyFields {
		if strings.Contains(fieldNameLower, strings.ToLower(apiField)) {
			return true
		}
	}

	// Check dynamic fields
	for _, apiField := range dynamicApiKeyFields {
		if strings.Contains(fieldNameLower, strings.ToLower(apiField)) {
			return true
		}
	}

	return false
}
