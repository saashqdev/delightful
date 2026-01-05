package model

// SignRequest represents the unified request payload for signing
type SignRequest struct {
	Data     string `json:"data" binding:"required"`
	SignType string `json:"sign_type,omitempty"` // Optional: signature type (hmac, ed25519, hash), defaults to hmac
}
