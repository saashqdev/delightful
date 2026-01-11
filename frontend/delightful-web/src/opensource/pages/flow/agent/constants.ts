// Status
export const Status = {
  enable: 7,
  disable: 8
}

// Approval flow status
export const Approvaltatus = {
  // Pending approval
  pending: 1,
  // In progress
  inprogress: 2,
  // Approved
  pass: 3,
  // Rejected
  reject: 4
}

// Approval flow status
export const ApprovalStatusMap = {
  [Approvaltatus.pending]: 'Pending',
  [Approvaltatus.inprogress]: 'In Progress',
  [Approvaltatus.pass]: 'Approved',
  [Approvaltatus.reject]: 'Rejected'
}



// Enterprise publish status
export const EntrepriseStatus = {
  // Not published
  unrelease: 5,
  // Published
  release: 6,
}

// Enterprise publish status
export const EntrepriseStatusMap = {
  [EntrepriseStatus.unrelease]: 'Not Published',
  [EntrepriseStatus.release]: 'Published',
}



// Platform publish status
export const PlatformStatus = {
  // Not listed
  unrelease: 9,
  // Under review
  audit: 10,
  // Listed
  release: 11,

}


// Platform publish status
export const PlatformStatusMap = {
  [PlatformStatus.unrelease]: 'Not Listed',
  [PlatformStatus.audit]: 'Under Review',
  [PlatformStatus.release]: 'Listed'
}


/**
 * AI assistant actionable items
 */
export const MenuKeys = {
	/** View version history */
	VersionRecord: 0,
	/** Edit */
	UpdateItem: 1,
	/** Publish */
	PublishItem: 2,
	/** Delete */
	DeleteItem: 3,
}





