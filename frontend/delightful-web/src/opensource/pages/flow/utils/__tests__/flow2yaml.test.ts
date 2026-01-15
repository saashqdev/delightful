import { describe, it, expect } from "vitest"
import fs from "fs"
import path from "path"
import { json2yaml, json2yamlString, jsonStr2yamlString } from "../flow2yaml"

describe.skip("Test flow2yaml module", () => {
	// Test basic object conversion
	describe("Basic object conversion", () => {
		it("Should correctly convert basic Flow object to DSL object", () => {
			const basicFlow = {
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [],
				nodes: [],
				global_variable: null,
				enabled: true,
				version_code: "1.0.0",
			}

			const dsl = json2yaml(basicFlow)

			// Check structure correctness
			expect(dsl.flow).toBeDefined()
			expect(dsl.flow.id).toBe(basicFlow.id)
			expect(dsl.flow.name).toBe(basicFlow.name)
			expect(dsl.flow.description).toBe(basicFlow.description)
			expect(dsl.flow.version).toBe(basicFlow.version_code)
			expect(dsl.flow.type).toBe("workflow") // Type 1 should convert to workflow
			expect(dsl.variables).toEqual([])
			expect(dsl.nodes).toEqual([])
			expect(dsl.edges).toEqual([])
		})

		it("Should correctly convert node type names", () => {
			const flowWithNodes = {
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [],
				nodes: [
					{
						id: "node-1",
						node_id: "node-1",
						node_type: "1", // Start node
						node_version: "v1",
						name: "Start Node",
						position: { x: 100, y: 100 },
						params: {},
						meta: {},
						next_nodes: [],
						step: 0,
						data: {},
						system_output: null,
					},
					{
						id: "node-2",
						node_id: "node-2",
						node_type: "2", // LLM node
						node_version: "v1",
						name: "LLM Node",
						position: { x: 200, y: 100 },
						params: {},
						meta: {},
						next_nodes: [],
						step: 0,
						data: {},
						system_output: null,
					},
				],
				global_variable: null,
				enabled: true,
				version_code: "1.0.0",
			}

			const dsl = json2yaml(flowWithNodes)

			// Check node conversion
			expect(dsl.nodes.length).toBe(2)
			expect(dsl.nodes[0].type).toBe("start")
			expect(dsl.nodes[1].type).toBe("llm")
		})
	})

	// Test edge conversion
	describe("Edge conversion", () => {
		it("Should correctly convert edge connections", () => {
			const flowWithEdges = {
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [
					{
						id: "edge-1",
						source: "node-1",
						target: "node-2",
						sourceHandle: "output",
						targetHandle: "input",
						type: "commonEdge",
						style: { stroke: "#000" },
						data: {},
					},
				],
				nodes: [],
				global_variable: null,
				enabled: true,
				version_code: "1.0.0",
			}

			const dsl = json2yaml(flowWithEdges)

			// Check edge conversion
			expect(dsl.edges.length).toBe(1)
			expect(dsl.edges[0].id).toBe("edge-1")
			expect(dsl.edges[0].source).toBe("node-1")
			expect(dsl.edges[0].target).toBe("node-2")
			expect(dsl.edges[0].sourceHandle).toBe("output")
			expect(dsl.edges[0].targetHandle).toBe("input")
		})
	})

	// Test global variable conversion
	describe("Global variable conversion", () => {
		it("Should correctly convert global variables", () => {
			const flowWithVariables = {
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [],
				nodes: [],
				global_variable: {
					variables: [
						{
							name: "testVar",
							type: "string",
							default_value: "test value",
							description: "A test variable",
						},
						{
							name: "numVar",
							type: "number",
							default_value: 123,
							description: "A number variable",
						},
					],
				},
				enabled: true,
				version_code: "1.0.0",
			}

			const dsl = json2yaml(flowWithVariables)

			// Check variable conversion
			expect(dsl.variables.length).toBe(2)
			expect(dsl.variables[0].name).toBe("testVar")
			expect(dsl.variables[0].type).toBe("string")
			expect(dsl.variables[0].default).toBe("test value")
			expect(dsl.variables[0].description).toBe("A test variable")

			expect(dsl.variables[1].name).toBe("numVar")
			expect(dsl.variables[1].type).toBe("number")
			expect(dsl.variables[1].default).toBe(123)
		})
	})

	// Test string output
	describe("YAML string generation", () => {
		it("Should generate valid YAML string", () => {
			const simpleFlow = {
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [],
				nodes: [],
				global_variable: null,
				enabled: true,
				version_code: "1.0.0",
			}

			const yamlString = json2yamlString(simpleFlow)

			// Check YAML string format
			expect(yamlString).toBeDefined()
			expect(typeof yamlString).toBe("string")
			expect(yamlString.includes("flow:")).toBe(true)
			expect(yamlString.includes("  id: test-flow-id")).toBe(true)
			expect(yamlString.includes("  name: Test Flow")).toBe(true)
			expect(yamlString.includes("  description: Test Description")).toBe(true)
			expect(yamlString.includes("  type: workflow")).toBe(true)
		})
	})

	// Test JSON string conversion
	describe("JSON string conversion", () => {
		it("Should correctly convert JSON string to YAML string", () => {
			const jsonString = JSON.stringify({
				id: "test-flow-id",
				name: "Test Flow",
				description: "Test Description",
				icon: "test-icon",
				type: 1,
				tool_set_id: "test-tool-set",
				edges: [],
				nodes: [],
				global_variable: null,
				enabled: true,
				version_code: "1.0.0",
			})

			const yamlString = jsonStr2yamlString(jsonString)

			// Check YAML string
			expect(yamlString).toBeDefined()
			expect(typeof yamlString).toBe("string")
			expect(yamlString.includes("flow:")).toBe(true)
			expect(yamlString.includes("  id: test-flow-id")).toBe(true)
		})

		it("Should handle invalid JSON string and throw error", () => {
			const invalidJsonString = "{invalid json}"

			expect(() => jsonStr2yamlString(invalidJsonString)).toThrow()
		})
	})

	// Test conversion with actual small cases
	describe("Actual case tests", () => {
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

		it("Should be able to convert small test case", () => {
			const yamlString = json2yamlString(smallJson)

			// Check YAML string contains key information
			expect(yamlString.includes(`flow:`)).toBe(true)
			expect(yamlString.includes(`  id: ${smallJson.id}`)).toBe(true)
			expect(yamlString.includes(`  name: ${smallJson.name}`)).toBe(true)

			// Check node information
			expect(yamlString.includes("nodes:")).toBe(true)
			expect(yamlString.includes("  - id: node-1")).toBe(true)
			expect(yamlString.includes("    type: start")).toBe(true)

			expect(yamlString.includes("  - id: node-2")).toBe(true)
			expect(yamlString.includes("    type: llm")).toBe(true)

			// Check edge information
			expect(yamlString.includes("edges:")).toBe(true)
			expect(yamlString.includes("  - id: edge-1")).toBe(true)

			// Check variable information
			expect(yamlString.includes("variables:")).toBe(true)
			expect(yamlString.includes("  - name: Test Variable")).toBe(true)
		})
	})
})
