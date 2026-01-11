import type { AuthMember } from "../types"
import { OperationTypes } from "../types"

/**
 * Determine if the current user can edit a member's permissions
 * @param auth Member to check
 * @param currentUserId Current user ID
 * @param currentUserAuth Current user's permission
 * @param authList Current permission list
 * @param originalAuthList Original permission list
 * @returns Whether editing is allowed
 */
export const canEditMemberAuth = (
	auth: AuthMember,
	currentUserId: string,
	currentUserAuth: OperationTypes | undefined,
	originalAuthList: AuthMember[],
): boolean => {
	// Current user cannot edit themselves
	if (auth.target_id === currentUserId) {
		return false
	}

	// Owner can edit everyone except themselves
	if (currentUserAuth === OperationTypes.Owner) {
		return auth.operation !== OperationTypes.Owner
	}

	// Special handling for admins
	if (currentUserAuth === OperationTypes.Admin) {
		// Cannot edit owner
		if (auth.operation === OperationTypes.Owner) {
			return false
		}

		// Find original permission
		const originalAuth = originalAuthList.find((item) => item.target_id === auth.target_id)

		// If original permission is admin, cannot edit
		if (originalAuth?.operation === OperationTypes.Admin) {
			return false
		}

		// Other cases can be edited
		return auth.target_id !== currentUserId
	}

	// Other permissions cannot edit anyone
	return false
}

/**
 * Determine whether to disable the select box
 * @param member Member to check
 * @param currentUserId Current user ID
 * @param currentUserAuth Current user's permission
 * @param authList Current permission list
 * @param originalAuthList Original permission list
 * @param creator Creator info
 * @returns Whether to disable
 */
export const isDisabled = (
	member: AuthMember,
	currentUserId: string,
	currentUserAuth: OperationTypes | undefined,
	originalAuthList: AuthMember[],
	creator: AuthMember | undefined,
): boolean => {
	// Current user is always disabled
	if (member.target_id === currentUserId) {
		return true
	}

	// Creator is always disabled
	if (member.target_id === creator?.target_id) {
		return true
	}

	// Special handling when current user is admin
	if (currentUserAuth === OperationTypes.Admin) {
		// Find original permission
		const originalAuth = originalAuthList.find((item) => item.target_id === member.target_id)

		// If original permission is owner, disable
		if (originalAuth?.operation === OperationTypes.Owner) {
			return true
		}

		// If original permission is admin, disable
		if (originalAuth?.operation === OperationTypes.Admin) {
			return true
		}

		// Other cases are not disabled, allow admin to add/modify regular users' permissions
		return false
	}

	// When current user is creator, can manage everyone except themselves
	return false
}

/**
 * Get disabled member ID list (for organization panel)
 * @param authList Current permission list
 * @param originalAuthList Original permission list
 * @param currentUserId Current user ID
 * @param currentUserAuth Current user's permission
 * @returns List of disabled member IDs
 */
export const getDisabledMemberIds = (
	originalAuthList: AuthMember[],
	currentUserId: string,
	currentUserAuth: OperationTypes | undefined,
): { id: string; dataType: string }[] => {
	return originalAuthList
		.filter((item) => {
			// Creator cannot be disabled
			if (item.operation === OperationTypes.Owner) {
				return true
			}

			// If current user is creator, only themselves cannot be disabled
			if (currentUserAuth === OperationTypes.Owner) {
				return item.target_id === currentUserId
			}

			// If current user is admin, creator, original admins and themselves cannot be disabled
			if (currentUserAuth === OperationTypes.Admin) {
				return item.operation === OperationTypes.Admin || item.target_id === currentUserId
			}

			// Other cases, all admins and creators cannot be disabled
			return item.operation === OperationTypes.Admin
		})
		.map((item) => ({
			id: item.target_id,
			dataType: "user", // This may need adjustment based on actual situation
		}))
}





