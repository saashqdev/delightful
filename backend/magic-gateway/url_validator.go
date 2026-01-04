package main

import (
	"fmt"
	"net"
	"net/url"
	"regexp"
	"strings"
)

// TargetURLRuleType defines the type of matching rule
type TargetURLRuleType string

const (
	// RuleTypeExact exact match for complete URL
	RuleTypeExact TargetURLRuleType = "exact"
	// RuleTypeDomain match entire domain and all subdomains
	RuleTypeDomain TargetURLRuleType = "domain"
	// RuleTypePrefix match URLs starting with specified prefix
	RuleTypePrefix TargetURLRuleType = "prefix"
	// RuleTypeRegex match URL using regular expression
	RuleTypeRegex TargetURLRuleType = "regex"
)

// TargetURLRule defines whitelist rules for target URLs
type TargetURLRule struct {
	Type        TargetURLRuleType // Rule type
	Pattern     string            // Match pattern
	Description string            // Rule description
	regex       *regexp.Regexp    // Compiled regular expression (for regex type)
}

// loadAllowedTargetURLs loads and parses URL whitelist rules from environment variables
func loadAllowedTargetURLs() {
	// First try to get from envVars (for testing), then from system environment variables
	whitelistEnv := ""
	if val, exists := envVars["MAGIC_GATEWAY_ALLOWED_TARGET_URLS"]; exists {
		whitelistEnv = val
	} else {
		whitelistEnv = getEnvWithDefault("MAGIC_GATEWAY_ALLOWED_TARGET_URLS", "")
	}

	if whitelistEnv == "" {
		logger.Println("Warning: MAGIC_GATEWAY_ALLOWED_TARGET_URLS environment variable not set, target parameter will be disabled")
		allowedTargetRules = []*TargetURLRule{}
		return
	}

	// Parse rules from environment variable
	// Format: type:pattern@description|type:pattern@description|...
	// Note: Use @ to separate description to avoid conflicts with : in URLs
	// Example: domain:example.com@Allow example.com|prefix:https://api.test.com/@Allow test API
	rules := strings.Split(whitelistEnv, "|")
	allowedTargetRules = make([]*TargetURLRule, 0, len(rules))

	for i, ruleStr := range rules {
		ruleStr = strings.TrimSpace(ruleStr)
		if ruleStr == "" {
			continue
		}

		// Parse rule components
		// Format: type:pattern@description (@ and description are optional)
		// Find first colon to separate type
		firstColon := strings.Index(ruleStr, ":")
		if firstColon == -1 {
			logger.Printf("Warning: Whitelist rule format error (rule %d): %s", i+1, ruleStr)
			continue
		}

		ruleType := TargetURLRuleType(strings.TrimSpace(ruleStr[:firstColon]))
		remaining := ruleStr[firstColon+1:]

		// Find @ to separate pattern and description
		atIndex := strings.Index(remaining, "@")
		var pattern, description string

		if atIndex > 0 {
			// Description provided
			pattern = strings.TrimSpace(remaining[:atIndex])
			description = strings.TrimSpace(remaining[atIndex+1:])
		} else {
			// No description
			pattern = strings.TrimSpace(remaining)
		}

		// Validate rule type
		switch ruleType {
		case RuleTypeExact, RuleTypeDomain, RuleTypePrefix:
			// Valid type
		case RuleTypeRegex:
			// Compile regular expression
			re, err := regexp.Compile(pattern)
			if err != nil {
				logger.Printf("Warning: Regular expression compilation failed (rule %d): %s, error: %v", i+1, pattern, err)
				continue
			}
			rule := &TargetURLRule{
				Type:        ruleType,
				Pattern:     pattern,
				Description: description,
				regex:       re,
			}
			allowedTargetRules = append(allowedTargetRules, rule)
			continue
		default:
			logger.Printf("Warning: Unknown rule type (rule %d): %s", i+1, ruleType)
			continue
		}

		rule := &TargetURLRule{
			Type:        ruleType,
			Pattern:     pattern,
			Description: description,
		}
		allowedTargetRules = append(allowedTargetRules, rule)
	}

	logger.Printf("Loaded %d target URL whitelist rules", len(allowedTargetRules))
	if debugMode {
		for i, rule := range allowedTargetRules {
			desc := rule.Description
			if desc == "" {
				desc = "No description"
			}
			logger.Printf("  Rule %d: [%s] %s - %s", i+1, rule.Type, rule.Pattern, desc)
		}
	}
}

// loadAllowedPrivateIPs loads and parses allowed internal IP list from environment variables
// Supports multi-node deployment scenarios, supports multiple delimiters and formats
func loadAllowedPrivateIPs() {
	// First try to get from envVars (for testing), then from system environment variables
	allowedIPsEnv := ""
	if val, exists := envVars["MAGIC_GATEWAY_ALLOWED_TARGET_IP"]; exists {
		allowedIPsEnv = val
	} else {
		allowedIPsEnv = getEnvWithDefault("MAGIC_GATEWAY_ALLOWED_TARGET_IP", "")
	}

	if allowedIPsEnv == "" {
		allowedPrivateIPs = []*net.IPNet{}
		if debugMode {
			logger.Println("MAGIC_GATEWAY_ALLOWED_TARGET_IP environment variable not set, all internal IPs will be blocked")
		}
		return
	}

	// Support multiple delimiters: comma, semicolon, newline, space
	// Format: IP1,IP2/CIDR,IP3;IP4 IP5\nIP6
	// Example: 192.168.1.1,10.0.0.0/8,172.16.0.0/12;192.168.2.0/24
	// Multi-node deployment example: 10.0.1.0/24,10.0.2.0/24,10.0.3.0/24
	allowedPrivateIPs = make([]*net.IPNet, 0)
	ipSet := make(map[string]bool) // For deduplication
	successCount := 0
	failCount := 0

	// Replace all delimiters with comma, then process uniformly
	normalized := strings.ReplaceAll(allowedIPsEnv, ";", ",")
	normalized = strings.ReplaceAll(normalized, "\n", ",")
	normalized = strings.ReplaceAll(normalized, "\r", ",")
	normalized = strings.ReplaceAll(normalized, " ", ",")

	ipStrings := strings.Split(normalized, ",")

	for i, ipStr := range ipStrings {
		ipStr = strings.TrimSpace(ipStr)
		if ipStr == "" {
			continue
		}

		// Try to parse as CIDR format
		var ipnet *net.IPNet
		var err error
		var cidrStr string

		// If contains slash, try to parse as CIDR
		if strings.Contains(ipStr, "/") {
			_, ipnet, err = net.ParseCIDR(ipStr)
			if err != nil {
				logger.Printf("Warning: Allowed internal IP format error (rule %d): %s, error: %v", i+1, ipStr, err)
				failCount++
				continue
			}
			cidrStr = ipnet.String()
		} else {
			// Single IP address, convert to /32 (IPv4) or /128 (IPv6)
			ip := net.ParseIP(ipStr)
			if ip == nil {
				logger.Printf("Warning: Allowed internal IP format error (rule %d): %s (invalid IP address)", i+1, ipStr)
				failCount++
				continue
			}
			// Create CIDR for single IP
			if ip.To4() != nil {
				// IPv4: /32
				_, ipnet, err = net.ParseCIDR(ipStr + "/32")
			} else {
				// IPv6: /128
				_, ipnet, err = net.ParseCIDR(ipStr + "/128")
			}
			if err != nil {
				logger.Printf("Warning: Allowed internal IP parsing failed (rule %d): %s, error: %v", i+1, ipStr, err)
				failCount++
				continue
			}
			cidrStr = ipnet.String()
		}

		// Deduplication: skip if same CIDR already exists
		if ipSet[cidrStr] {
			if debugMode {
				logger.Printf("Skip duplicate allowed internal IP rule: %s", cidrStr)
			}
			continue
		}

		ipSet[cidrStr] = true
		allowedPrivateIPs = append(allowedPrivateIPs, ipnet)
		successCount++
	}

	// Count IPv4 and IPv6
	ipv4Count := 0
	ipv6Count := 0
	for _, ipnet := range allowedPrivateIPs {
		if ipnet.IP.To4() != nil {
			ipv4Count++
		} else {
			ipv6Count++
		}
	}

	logger.Printf("Loaded %d allowed internal IP rules (success: %d, failed: %d, IPv4: %d, IPv6: %d)",
		len(allowedPrivateIPs), successCount, failCount, ipv4Count, ipv6Count)

	if debugMode {
		for i, ipnet := range allowedPrivateIPs {
			ipType := "IPv4"
			if ipnet.IP.To4() == nil {
				ipType = "IPv6"
			}
			logger.Printf("  Allowed internal IP %d [%s]: %s", i+1, ipType, ipnet.String())
		}
	}
}

// isPrivateIP checks if IP address is a private/internal address
func isPrivateIP(ip net.IP) bool {
	// First check if in allowed internal IP list
	// If in allow list, return false (allow through)
	for _, allowedIPNet := range allowedPrivateIPs {
		if allowedIPNet != nil && allowedIPNet.Contains(ip) {
			return false
		}
	}

	// Check if loopback address
	if ip.IsLoopback() {
		return true
	}

	// Check private IP ranges
	privateRanges := []string{
		"10.0.0.0/8",     // Private network
		"172.16.0.0/12",  // Private network
		"192.168.0.0/16", // Private network
		"169.254.0.0/16", // Link-local address
		"127.0.0.0/8",    // Loopback address
		"::1/128",        // IPv6 loopback address
		"fc00::/7",       // IPv6 private address
		"fe80::/10",      // IPv6 link-local address
	}

	for _, cidr := range privateRanges {
		_, subnet, _ := net.ParseCIDR(cidr)
		if subnet != nil && subnet.Contains(ip) {
			return true
		}
	}

	return false
}

// validateTargetURL performs comprehensive validation on target URL
func validateTargetURL(targetURL string) error {
	if targetURL == "" {
		return nil // Allow empty target (will use other methods)
	}

	// Parse URL
	parsedURL, err := url.Parse(targetURL)
	if err != nil {
		return fmt.Errorf("URL format error: %v", err)
	}

	// Check protocol - only allow http and https
	if parsedURL.Scheme != "http" && parsedURL.Scheme != "https" {
		return fmt.Errorf("Unsupported protocol: %s (only http/https supported)", parsedURL.Scheme)
	}

	// Extract hostname
	hostname := parsedURL.Hostname()
	if hostname == "" {
		return fmt.Errorf("Missing hostname in URL")
	}

	// Prevent URL tricks like @, :, etc
	if strings.Contains(hostname, "@") {
		return fmt.Errorf("Hostname contains illegal characters")
	}

	// Check if port number is within reasonable range
	if port := parsedURL.Port(); port != "" {
		// Block common internal/admin ports
		blockedPorts := []string{"22", "23", "25", "3306", "5432", "6379", "27017", "9200"}
		for _, blocked := range blockedPorts {
			if port == blocked {
				return fmt.Errorf("Access to port forbidden: %s", port)
			}
		}
	}

	// Check if it's an IP address
	if ip := net.ParseIP(hostname); ip != nil {
		// Check if it's a private IP
		if isPrivateIP(ip) {
			return fmt.Errorf("Access to private IP addresses forbidden: %s", hostname)
		}
	} else {
		// Resolve hostname to check private IP (prevent DNS rebinding attack)
		ips, err := net.LookupIP(hostname)
		if err != nil {
			// DNS resolution failed, but we allow to continue
			// If domain doesn't exist, actual request will still fail
			if debugMode {
				logger.Printf("DNS resolution failed: %s, error: %v", hostname, err)
			}
		} else {
			// Check if resolved IP is private IP
			for _, ip := range ips {
				if isPrivateIP(ip) {
					return fmt.Errorf("Domain %s resolves to private IP address: %s", hostname, ip.String())
				}
			}
		}
	}
	logger.Printf("targetURL: %s, allowedTargetRules: %v", targetURL, allowedTargetRules)
	// Check whitelist rules
	if len(allowedTargetRules) == 0 {
		return fmt.Errorf("URL whitelist not configured, target parameter forbidden")
	}

	for _, rule := range allowedTargetRules {
		if matchTargetURL(targetURL, parsedURL, rule) {
			if debugMode {
				logger.Printf("URL matches whitelist rule: [%s] %s", rule.Type, rule.Pattern)
			}
			return nil // URL is allowed
		}
	}

	return fmt.Errorf("URL not in whitelist")
}

// matchTargetURL checks if target URL matches whitelist rule
func matchTargetURL(targetURL string, parsedURL *url.URL, rule *TargetURLRule) bool {
	switch rule.Type {
	case RuleTypeExact:
		// Exact match (ignore trailing slash)
		pattern := strings.TrimSuffix(rule.Pattern, "/")
		target := strings.TrimSuffix(targetURL, "/")
		return pattern == target

	case RuleTypeDomain:
		// Match domain and all subdomains
		hostname := parsedURL.Hostname()
		pattern := strings.TrimPrefix(rule.Pattern, ".")

		// Exact domain match or subdomain match
		return hostname == pattern || strings.HasSuffix(hostname, "."+pattern)

	case RuleTypePrefix:
		// Prefix match
		return strings.HasPrefix(targetURL, rule.Pattern)

	case RuleTypeRegex:
		// Regular expression match
		if rule.regex != nil {
			return rule.regex.MatchString(targetURL)
		}
		return false

	default:
		return false
	}
}

// isTargetURLAllowed checks if target URL is allowed (wrapper function for backward compatibility)
func isTargetURLAllowed(targetURL string) bool {
	err := validateTargetURL(targetURL)
	if err != nil {
		if debugMode {
			logger.Printf("Target URL validation failed: %s, reason: %v", targetURL, err)
		}
		return false
	}
	return true
}
