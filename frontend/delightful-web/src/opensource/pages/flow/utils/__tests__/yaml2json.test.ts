import { describe, it, expect } from "vitest"
import fs from "fs"
import path from "path"
import { yaml2json, yamlString2json, yamlString2jsonString } from "../yaml2json"
import { json2yamlString } from "../flow2yaml"

describe.skip("Test yaml2json module", () => {
	// Test basic YAML conversion
	describe("Basic YAML conversion", () => {
		it("Should correctly convert basic YAML object to Flow object", () => {
			const yamlDSL = {
				flow: {
					id: "test-flow-id",
					name: "Test Flow",
					description: "Test Description",
					version: "1.0.0",
					type: "workflow",
					icon: "test-icon",
					enabled: true,
				},
				variables: [],
				nodes: [],
				edges: [],
			}

			const flow = yaml2json(yamlDSL)

			// Check basic information
			expect(flow.id).toBe(yamlDSL.flow.id)
			expect(flow.name).toBe(yamlDSL.flow.name)
			expect(flow.description).toBe(yamlDSL.flow.description)
			expect(flow.version_code).toBe(yamlDSL.flow.version)
			expect(flow.type).toBe(1) // workflow should convert to 1
			expect(flow.enabled).toBe(yamlDSL.flow.enabled)
			expect(Array.isArray(flow.nodes)).toBe(true)
			expect(Array.isArray(flow.edges)).toBe(true)
		})

		it("Should correctly handle different flow types", () => {
			const chatDSL = {
				flow: {
					id: "test-flow-id",
					name: "Test flow",
					description: "Test description",
					version: "1.0.0",
					type: "chat", // Chat type
					icon: "test-icon",
					enabled: true,
				},
				variables: [],
				nodes: [],
				edges: [],
			}

			const flow = yaml2json(chatDSL)

			// Check flow type
			expect(flow.type).toBe(2) // chat should convert to 2
		})
	})

	// Test node conversion
	describe("Node conversion", () => {
		it("Should correctly convert node types", () => {
			const yamlWithNodes = {
				flow: {
					id: "test-flow-id",
					name: "Test flow",
					description: "Test description",
					version: "1.0.0",
					type: "workflow",
				},
				variables: [],
				nodes: [
					{
						id: "node-1",
						type: "start",
						name: "Start Node",
						position: { x: 100, y: 100 },
						params: {},
						version: "v1",
						next_nodes: [],
					},
					{
						id: "node-2",
						type: "llm",
						name: "LLM Node",
						position: { x: 200, y: 100 },
						params: {
							model: "gpt-4",
							temperature: 0.7,
						},
						version: "v1",
						next_nodes: [],
					},
				],
				edges: [],
			}

			const flow = yaml2json(yamlWithNodes)

			// Check nodes
			expect(flow.nodes.length).toBe(2)
			expect(flow.nodes[0].node_type).toBe("1") // start should convert to 1
			expect(flow.nodes[0].name).toBe("Start node")

			expect(flow.nodes[1].node_type).toBe("2") // llm should convert to 2
			expect(flow.nodes[1].name).toBe("LLM node")
			expect(flow.nodes[1].params.model).toBe("gpt-4")
		})
	})

	// Test edge conversion
	describe("Edge conversion", () => {
		it("Should correctly convert edges and add default styles", () => {
			const yamlWithEdges = {
				flow: {
					id: "test-flow-id",
					name: "Test flow",
					version: "1.0.0",
					type: "workflow",
				},
				variables: [],
				nodes: [],
				edges: [
					{
						id: "edge-1",
						source: "node-1",
						target: "node-2",
						sourceHandle: "output",
						targetHandle: "input",
					},
				],
			}

			const flow = yaml2json(yamlWithEdges)

			// Check edges
			expect(flow.edges.length).toBe(1)
			expect(flow.edges[0].id).toBe("edge-1")
			expect(flow.edges[0].source).toBe("node-1")
			expect(flow.edges[0].target).toBe("node-2")
			expect(flow.edges[0].sourceHandle).toBe("output")
			expect(flow.edges[0].targetHandle).toBe("input")
			expect(flow.edges[0].type).toBe("commonEdge")

			// Check if default styles were added
			expect(flow.edges[0].markerEnd).toBeDefined()
			expect(flow.edges[0].style).toBeDefined()
			expect(flow.edges[0].data).toBeDefined()
		})
	})

	// Test global variable conversion
	describe("Global variable conversion", () => {
		it("Should correctly convert global variables", () => {
			const yamlWithVariables = {
				flow: {
					id: "test-flow-id",
					name: "Test flow",
					version: "1.0.0",
					type: "workflow",
				},
				variables: [
					{
						name: "testVar",
						type: "string",
						default: "test value",
						description: "A test variable",
					},
					{
						name: "numVar",
						type: "number",
						default: 123,
						description: "A number variable",
					},
				],
				nodes: [],
				edges: [],
			}

			const flow = yaml2json(yamlWithVariables)

			// Check global variables
			expect(flow.global_variable).toBeDefined()
			expect(flow.global_variable.variables.length).toBe(2)
			expect(flow.global_variable.variables[0].name).toBe("testVar")
			expect(flow.global_variable.variables[0].type).toBe("string")
			expect(flow.global_variable.variables[0].default_value).toBe("test value")
			expect(flow.global_variable.variables[0].description).toBe("A test variable")

			expect(flow.global_variable.variables[1].name).toBe("numVar")
			expect(flow.global_variable.variables[1].type).toBe("number")
			expect(flow.global_variable.variables[1].default_value).toBe(123)
		})

		it("Should handle empty variable list", () => {
			const yamlWithoutVariables = {
				flow: {
					id: "test-flow-id",
					name: "Test flow",
					version: "1.0.0",
					type: "workflow",
				},
				variables: [],
				nodes: [],
				edges: [],
			}

			const flow = yaml2json(yamlWithoutVariables)

			// Check global variable is null
			expect(flow.global_variable).toBeNull()
		})
	})

	// Test YAML string parsing
	describe("YAML string parsing", () => {
		it("Should correctly parse YAML string", () => {
			const yamlString = `
flow:
  id: test-flow-id
  name: Test Flow
  description: Test Description
  version: 1.0.0
  type: workflow
  icon: test-icon
  enabled: true
variables: []
nodes: []
edges: []
      `

			const flow = yamlString2json(yamlString)

			// Check basic information
			expect(flow.id).toBe("test-flow-id")
			expect(flow.name).toBe("Test flow")
			expect(flow.description).toBe("Test description")
			expect(flow.version_code).toBe("1.0.0")
			expect(flow.type).toBe(1)
			expect(flow.icon).toBe("test-icon")
			expect(flow.enabled).toBe(true)
		})

		it("Should handle malformed YAML string and throw error", () => {
			const invalidYamlString = "invalid yaml: [\n]test:"

			expect(() => yamlString2json(invalidYamlString)).toThrow()
		})
	})

	// Test YAML to JSON string conversion
	describe("YAML to JSON string conversion", () => {
		it("Should correctly convert YAML string to JSON string", () => {
			const yamlString = `
flow:
  id: test-flow-id
  name: Test Flow
  description: Test Description
  version: 1.0.0
  type: workflow
variables: []
nodes: []
edges: []
      `

			const jsonString = yamlString2jsonString(yamlString)

			// Check JSON string
			expect(jsonString).toBeDefined()
			expect(typeof jsonString).toBe("string")

			// Parse back to object to check
			const parsedJson = JSON.parse(jsonString)
			expect(parsedJson.id).toBe("test-flow-id")
			expect(parsedJson.name).toBe("Test flow")
			expect(parsedJson.description).toBe("Test description")
			expect(parsedJson.type).toBe(1)
		})
	})

	// Test JSON->YAML->JSON round-trip conversion
	describe("Round-trip conversion tests", () => {
		it("Should preserve data integrity in JSON->YAML->JSON conversion", () => {
			// Create test data
			const originalJson = {
				id: "test-flow-id",
				name: "Test flow",
				description: "Test description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [
					{
						id: "edge-1",
						source: "node-1",
						target: "node-2",
						type: "commonEdge",
					},
				],
				nodes: [
					{
						id: "node-1",
						node_id: "node-1",
						node_type: "1",
						node_version: "v1",
					name: "Start Node",
						position: { x: 100, y: 100 },
						params: {},
						meta: {},
						next_nodes: ["node-2"],
						step: 0,
						data: {},
						system_output: null,
					},
					{
						id: "node-2",
						node_id: "node-2",
						node_type: "2",
						node_version: "v1",
					name: "LLM Node",
						position: { x: 200, y: 100 },
						params: { model: "gpt-4" },
						meta: {},
						next_nodes: [],
						step: 0,
						data: {},
						system_output: null,
					},
				],
				global_variable: {
					variables: [
						{
							name: "testVar",
							type: "string",
							default_value: "test value",
							description: "A test variable",
						},
					],
				},
				enabled: true,
				version_code: "1.0.0",
			}

			// JSON -> YAML
			const yamlString = json2yamlString(originalJson)

			// YAML -> JSON
			const convertedJson = yamlString2json(yamlString)

			// Check key properties
			expect(convertedJson.id).toBe(originalJson.id)
			expect(convertedJson.name).toBe(originalJson.name)
			expect(convertedJson.description).toBe(originalJson.description)
			expect(convertedJson.type).toBe(originalJson.type)

			// Check nodes
			expect(convertedJson.nodes.length).toBe(originalJson.nodes.length)
			expect(convertedJson.nodes[0].id).toBe(originalJson.nodes[0].id)
			expect(convertedJson.nodes[0].node_type).toBe(originalJson.nodes[0].node_type)
			expect(convertedJson.nodes[1].id).toBe(originalJson.nodes[1].id)
			expect(convertedJson.nodes[1].node_type).toBe(originalJson.nodes[1].node_type)
			expect(convertedJson.nodes[1].params.model).toBe(originalJson.nodes[1].params.model)

			// Check edges
			expect(convertedJson.edges.length).toBe(originalJson.edges.length)
			expect(convertedJson.edges[0].source).toBe(originalJson.edges[0].source)
			expect(convertedJson.edges[0].target).toBe(originalJson.edges[0].target)

			// Check global variable
			expect(convertedJson.global_variable).toBeDefined()
			expect(convertedJson.global_variable.variables[0].name).toBe(
				originalJson.global_variable.variables[0].name,
			)
			expect(convertedJson.global_variable.variables[0].default_value).toBe(
				originalJson.global_variable.variables[0].default_value,
			)
		})
	})

	// Test with actual small case conversion
	describe("Small actual case test", () => {
		let smallJson: any

		// Read test data
		beforeEach(() => {
			const filePath = path.resolve(
				__dirname,
				"../../components/FlowAssistant/all_flow_nodes_small.json",
			)
			const fileContent = fs.readFileSync(filePath, "utf-8")
			smallJson = JSON.parse(fileContent)
		})

		it("Should correctly perform JSON->YAML->JSON conversion and maintain data integrity", () => {
			// JSON -> YAML
			const yamlString = json2yamlString(smallJson)

			// YAML -> JSON
			const convertedJson = yamlString2json(yamlString)

			// Check basic information
			expect(convertedJson.id).toBe(smallJson.id)
			expect(convertedJson.name).toBe(smallJson.name)
			expect(convertedJson.description).toBe(smallJson.description)
			expect(convertedJson.type).toBe(smallJson.type)

			// Check node count and content
			expect(convertedJson.nodes.length).toBe(smallJson.nodes.length)
			expect(convertedJson.nodes[0].id).toBe(smallJson.nodes[0].id)
			expect(convertedJson.nodes[0].node_type).toBe(smallJson.nodes[0].node_type)
			expect(convertedJson.nodes[0].name).toBe(smallJson.nodes[0].name)

			expect(convertedJson.nodes[1].id).toBe(smallJson.nodes[1].id)
			expect(convertedJson.nodes[1].node_type).toBe(smallJson.nodes[1].node_type)
			expect(convertedJson.nodes[1].name).toBe(smallJson.nodes[1].name)

			// Check LLM node parameters
			const originalLLM = smallJson.nodes.find((n: any) => n.node_type === "2")
			const convertedLLM = convertedJson.nodes.find((n: any) => n.node_type === "2")
			expect(convertedLLM.params.model).toBe(originalLLM.params.model)
			expect(convertedLLM.params.user_prompt).toBe(originalLLM.params.user_prompt)
			expect(convertedLLM.params.system_prompt).toBe(originalLLM.params.system_prompt)

			// Check edges
			expect(convertedJson.edges.length).toBe(smallJson.edges.length)
			expect(convertedJson.edges[0].source).toBe(smallJson.edges[0].source)
			expect(convertedJson.edges[0].target).toBe(smallJson.edges[0].target)

			// Check global variable
			if (smallJson.global_variable && convertedJson.global_variable) {
				expect(convertedJson.global_variable.variables[0].name).toBe(
					smallJson.global_variable.variables[0].name,
				)
				expect(convertedJson.global_variable.variables[0].default_value).toBe(
					smallJson.global_variable.variables[0].default_value,
				)
			}
		})
	})
})
