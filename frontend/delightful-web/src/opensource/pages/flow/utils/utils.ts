/**
 * Flow toolset unified entry point
 * Provides unified access point for DSL and Flow related tools
 */

import * as dsl from "./dsl"
import * as flow from "./flow"

// Directly export commonly used tools
import { DSLConverter } from "./dsl/dslConverter"

// Export tool modules
export {
	// DSL related tools
	dsl,

	// Flow related tools
	flow,

	// Directly export commonly used tools
	DSLConverter,
}

// Export default interface
export default {
	dsl,
	flow,
	DSLConverter,
}
