import ShareIcon from "@/opensource/pages/share/assets/icon/replay_icon.svg"
import { Button, Input } from "antd"
import { createStyles } from "antd-style"
import { useCallback, useEffect, useState } from "react"

// Define the styles using createStyles
const useStyles = createStyles(({ token }) => {
	return {
		container: {
			width: "100%",
			height: "100%",
			display: "flex",
			flexDirection: "column",
			alignItems: "center",
			justifyContent: "center",
			padding: "32px",
			maxWidth: "400px",
			margin: "0 auto",
		},
		icon: {
			width: "60px",
			height: "60px",
			margin: "0 auto 20px",
			display: "flex",
			alignItems: "center",
			justifyContent: "center",
			borderRadius: "50%",
			backgroundColor: token.delightfulColorUsages.primaryLight.default,
		},
		title: {
			fontSize: "18px",
			fontWeight: "500",
			marginBottom: "16px",
			color: token.delightfulColorUsages.text[0],
		},
		description: {
			fontSize: "14px",
			color: token.delightfulColorUsages.text[1],
			marginBottom: "24px",
		},
		inputContainer: {
			display: "flex",
			alignItems: "center",
			width: "100%",
			marginBottom: "16px",
		},
		input: {
			flex: 1,
			marginRight: "8px",
		},
		button: {
			backgroundColor: token.delightfulColorUsages.primary.default,
			borderColor: token.delightfulColorUsages.primary.default,
			"&:hover": {
				backgroundColor: token.delightfulColorUsages.primary.hover,
				borderColor: token.delightfulColorUsages.primary.hover,
			},
		},
	}
})

const STORAGE_KEY_PREFIX = "share_password_"

interface PasswordVerificationProps {
	resourceId: string
	initialPassword?: string
	onVerifySuccess: (data: any) => void
	onVerifyFail?: (error: any) => void
	getShareData: (params: { resource_id: string; password?: string }) => Promise<any>
}

export default function PasswordVerification({
	resourceId,
	initialPassword = "",
	onVerifySuccess,
	onVerifyFail,
	getShareData,
}: PasswordVerificationProps) {
	const [password, setPassword] = useState(initialPassword)
	const [loading, setLoading] = useState(false)
	const { styles } = useStyles()

	// Handle password input change
	const handlePasswordChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		// Convert to uppercase and limit length to 5
		const value = e.target.value.toUpperCase().slice(0, 5)
		setPassword(value)
	}

	// Store password to sessionStorage
	const savePasswordToStorage = (resourceIdToSave: string, passwordToSave: string) => {
		try {
			sessionStorage.setItem(`${STORAGE_KEY_PREFIX}${resourceIdToSave}`, passwordToSave)
		} catch (e) {
			console.error("Failed to save password to sessionStorage", e)
		}
	}

	// Get password from sessionStorage
	const getPasswordFromStorage = (resourceIdToGet: string): string | null => {
		try {
			return sessionStorage.getItem(`${STORAGE_KEY_PREFIX}${resourceIdToGet}`)
		} catch (e) {
			console.error("Failed to get password from sessionStorage", e)
			return null
		}
	}

	// Handle verification button click
	const handleVerify = useCallback(async () => {
		if (!password) return

		try {
			setLoading(true)
			const result = await getShareData({ resource_id: resourceId, password })

			if (result) {
				// Save password after successful verification
				savePasswordToStorage(resourceId, password)
				onVerifySuccess(result)
			}
		} catch (error) {
			onVerifyFail?.(error)
		} finally {
			setLoading(false)
		}
	}, [password, resourceId, onVerifySuccess, onVerifyFail])

	// Auto-verify initial password or get password from sessionStorage to verify
	useEffect(() => {
		const tryVerifyWithStoredPassword = async () => {
			// If there is an initial password, use it to verify
			if (initialPassword) {
				return
			}

			// When there's no initial password, try to get from sessionStorage
			const storedPassword = getPasswordFromStorage(resourceId)
			if (storedPassword && !password) {
				setPassword(storedPassword)
				try {
					setLoading(true)
					const result = await getShareData({
						resource_id: resourceId,
						password: storedPassword,
					})
					if (result) {
						onVerifySuccess(result)
					}
				} catch (error) {
					// Verification failed, no action taken, user needs to manually enter password
					console.log("Verification with stored password failed, user needs to manually enter")
				} finally {
					setLoading(false)
				}
			}
		}

		tryVerifyWithStoredPassword()
	}, [resourceId, initialPassword, onVerifySuccess, password])

	// Handle initial password verification separately
	useEffect(() => {
		if (initialPassword) {
			handleVerify()
		}
	}, [initialPassword, handleVerify])

	// Handle Enter key press
	const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
		if (e.key === "Enter") {
			handleVerify()
		}
	}

	return (
		<div className={styles.container}>
			<div className={styles.icon}>
				<img src={ShareIcon} alt="" />
			</div>
			<div className={styles.title}>This playback has password protection enabled</div>
			<div className={styles.description}>Please enter password to view playback</div>
			<div className={styles.inputContainer}>
				<Input
					className={styles.input}
					placeholder="Please enter access password"
					value={password}
					onChange={handlePasswordChange}
					onKeyDown={handleKeyDown}
					maxLength={5}
					autoFocus
				/>
				<Button
					type="primary"
					className={styles.button}
					onClick={handleVerify}
					loading={loading}
				>
					View
				</Button>
			</div>
		</div>
	)
}
