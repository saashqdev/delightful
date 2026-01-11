import { describe, it, expect } from "vitest"
import fs from "fs"
import path from "path"
import { FlowConverter } from "../flowConverter"

describe.skip("Test FlowConverter", () => {
	// Test simple JSON and YAML conversion
	describe("Simple case test", () => {
		const simpleJson = {
			id: "DELIGHTFUL-FLOW-test",
			name: "Simple test",
			nodes: [{ id: "node-1", type: "1" }],
			edges: [],
		}

		it("Should convert simple JSON object to YAML string", () => {
			const yaml = FlowConverter.jsonToYamlString(simpleJson)
			expect(yaml).toBeDefined()
			expect(yaml.includes("id: DELIGHTFUL-FLOW-test")).toBe(true)
			expect(yaml.includes("name: Simple test")).toBe(true)
		})

		it("Should convert YAML string back to JSON object while maintaining data consistency", () => {
			const yaml = FlowConverter.jsonToYamlString(simpleJson)
			const jsonFromYaml = FlowConverter.yamlToJson(yaml)

			// Check basic fields
			expect(jsonFromYaml.id).toBe(simpleJson.id)
			expect(jsonFromYaml.name).toBe(simpleJson.name)
			expect(Array.isArray(jsonFromYaml.nodes)).toBe(true)
			expect(Array.isArray(jsonFromYaml.edges)).toBe(true)
		})
	})

	// Test small complete case
	describe("Small complete case test", () => {
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

		it("Should convert small JSON object to YAML and maintain data integrity", () => {
			// JSON to YAML
			const yaml = FlowConverter.jsonToYamlString(smallJson)
			expect(yaml).toBeDefined()

			// YAML back to JSON
			const jsonFromYaml = FlowConverter.yamlToJson(yaml)

			// Check basic information
			expect(jsonFromYaml.id).toBe(smallJson.id)
			expect(jsonFromYaml.name).toBe(smallJson.name)
			expect(jsonFromYaml.description).toBe(smallJson.description)
			expect(jsonFromYaml.type).toBe(smallJson.type)

			// Check node count and basic information
			expect(jsonFromYaml.nodes.length).toBe(smallJson.nodes.length)
			expect(jsonFromYaml.nodes[0].id).toBe(smallJson.nodes[0].id)
			expect(jsonFromYaml.nodes[0].node_type).toBe(smallJson.nodes[0].node_type)
			expect(jsonFromYaml.nodes[0].name).toBe(smallJson.nodes[0].name)

			// Check edges
			expect(jsonFromYaml.edges.length).toBe(smallJson.edges.length)
			expect(jsonFromYaml.edges[0].source).toBe(smallJson.edges[0].source)
			expect(jsonFromYaml.edges[0].target).toBe(smallJson.edges[0].target)

			// Check global variables
			expect(jsonFromYaml.global_variable).toBeDefined()
			if (smallJson.global_variable && jsonFromYaml.global_variable) {
				expect(jsonFromYaml.global_variable.variables[0].name).toBe(
					smallJson.global_variable.variables[0].name,
				)
				expect(jsonFromYaml.global_variable.variables[0].default_value).toBe(
					smallJson.global_variable.variables[0].default_value,
				)
			}
		})

		it("Converted YAML should be convertible back to JSON and match original JSON", () => {
			// 1. JSON -> YAML
			const yaml = FlowConverter.jsonToYamlString(smallJson)

			// 2. YAML -> JSON
			const convertedJson = FlowConverter.yamlToJson(yaml)

			// 3. Convert again: YAML -> JSON -> YAML to ensure stability of multiple conversions
			const secondYaml = FlowConverter.jsonToYamlString(convertedJson)
			const secondJson = FlowConverter.yamlToJson(secondYaml)

			// Check that key properties remain consistent
			function checkObjectEquality(original: any, converted: any, path = "") {
				// Check basic information
				expect(converted.id, `${path}.id`).toBe(original.id)
				expect(converted.name, `${path}.name`).toBe(original.name)

				// Check node matching
				expect(converted.nodes.length, `${path}.nodes.length`).toBe(original.nodes.length)
				for (let i = 0; i < original.nodes.length; i++) {
					expect(converted.nodes[i].id, `${path}.nodes[${i}].id`).toBe(
						original.nodes[i].id,
					)
					expect(converted.nodes[i].node_type, `${path}.nodes[${i}].node_type`).toBe(
						original.nodes[i].node_type,
					)
				}

				// Check edge matching
				expect(converted.edges.length, `${path}.edges.length`).toBe(original.edges.length)
				for (let i = 0; i < original.edges.length; i++) {
					expect(converted.edges[i].source, `${path}.edges[${i}].source`).toBe(
						original.edges[i].source,
					)
					expect(converted.edges[i].target, `${path}.edges[${i}].target`).toBe(
						original.edges[i].target,
					)
				}
			}

			// Check first conversion consistency
			checkObjectEquality(smallJson, convertedJson, "first-conversion")

			// Check second conversion consistency
			checkObjectEquality(convertedJson, secondJson, "second-conversion")
		})
	})

	// Test API interface
	describe("Test API interface methods", () => {
		const testJson = {
			id: "test",
			name: "test",
			nodes: [{ id: "node1" }],
			edges: [],
		}
		const testYaml = `flow:
  id: test
  name: test
variables: []
nodes:
  - id: node1
edges: []`

		it("jsonToYamlString method should work correctly", () => {
			const yaml = FlowConverter.jsonToYamlString(testJson)
			expect(yaml).toBeDefined()
			expect(typeof yaml).toBe("string")
		})

		it("yamlToJsonString method should work correctly", () => {
			const json = FlowConverter.yamlToJsonString(testYaml)
			expect(json).toBeDefined()
			expect(typeof json).toBe("string")
			// Should be a valid JSON string
			expect(() => JSON.parse(json)).not.toThrow()
		})

		it("jsonStringToYamlString method should work correctly", () => {
			const jsonString = JSON.stringify(testJson)
			const yaml = FlowConverter.jsonStringToYamlString(jsonString)
			expect(yaml).toBeDefined()
			expect(typeof yaml).toBe("string")
		})
	})

	// Test edge cases and error handling
	describe("Edge cases and error handling", () => {
		it("Handle empty object", () => {
			const emptyJson = {}
			expect(() => FlowConverter.jsonToYamlString(emptyJson)).not.toThrow()
		})

		it("Handle object with missing fields", () => {
			const incompleteJson = { id: "test" } // Missing required nodes and edges fields
			expect(() => FlowConverter.jsonToYamlString(incompleteJson)).not.toThrow()
		})
	})
})





