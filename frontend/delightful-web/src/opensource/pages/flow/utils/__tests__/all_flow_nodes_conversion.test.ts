import { describe, it, expect, beforeAll } from "vitest"
import fs from "fs"
import path from "path"
import FlowConverter from "../flowConverter"

// Because this test processes large JSON, it may timeout, so increase the timeout
describe.skip("Large JSON conversion test", () => {
	let allFlowNodesJson: any

	// Read large test data
	beforeAll(() => {
		const filePath = path.resolve(
			__dirname,
			"../../components/FlowAssistant/all_flow_nodes.json",
		)
		const fileContent = fs.readFileSync(filePath, "utf-8")
		allFlowNodesJson = JSON.parse(fileContent)
	}, 10000) // 10 seconds timeout

	it("should be able to convert large Flow JSON to YAML and maintain structure integrity", () => {
		// JSON -> YAML
		const yaml = FlowConverter.jsonToYamlString(allFlowNodesJson)

		// Basic check of YAML string format
		expect(yaml).toBeDefined()
		expect(typeof yaml).toBe("string")
		expect(yaml.includes("flow:")).toBe(true)
		expect(yaml.includes(`  id: ${allFlowNodesJson.id}`)).toBe(true)
		expect(yaml.includes(`  name: ${allFlowNodesJson.name}`)).toBe(true)

		// Write to file for manual inspection
		const yamlOutputPath = path.resolve(
			__dirname,
			"../../components/FlowAssistant/all_flow_nodes.yaml",
		)
		fs.writeFileSync(yamlOutputPath, yaml)

		// YAML -> JSON (convert back)
		const convertedJson = FlowConverter.yamlToJson(yaml)

		// Write converted JSON to file for manual comparison
		const jsonOutputPath = path.resolve(
			__dirname,
			"../../components/FlowAssistant/all_flow_nodes_converted.json",
		)
		fs.writeFileSync(jsonOutputPath, JSON.stringify(convertedJson, null, 2))

		// Basic information comparison
		expect(convertedJson.id).toBe(allFlowNodesJson.id)
		expect(convertedJson.name).toBe(allFlowNodesJson.name)
		expect(convertedJson.description).toBe(allFlowNodesJson.description)
		expect(convertedJson.type).toBe(allFlowNodesJson.type)

		// Check node count
		expect(convertedJson.nodes.length).toBe(allFlowNodesJson.nodes.length)

		// Check edge count
		expect(convertedJson.edges.length).toBe(allFlowNodesJson.edges.length)

		// Deep check of some key nodes
		// Ensure node types and IDs are all correct
		allFlowNodesJson.nodes.forEach((originalNode: any, index: number) => {
			const convertedNode = convertedJson.nodes.find((n: any) => n.id === originalNode.id)
			expect(convertedNode).toBeDefined()
			expect(convertedNode.node_type).toBe(originalNode.node_type)
			expect(convertedNode.name).toBe(originalNode.name)
		})

		// Check edge connections
		allFlowNodesJson.edges.forEach((originalEdge: any, index: number) => {
			const convertedEdge = convertedJson.edges.find((e: any) => e.id === originalEdge.id)
			expect(convertedEdge).toBeDefined()
			expect(convertedEdge.source).toBe(originalEdge.source)
			expect(convertedEdge.target).toBe(originalEdge.target)
		})
	}, 30000) // 30 seconds timeout

	it("conversion result should run successfully", () => {
		// Complete conversion flow: JSON -> YAML -> JSON
		const yaml = FlowConverter.jsonToYamlString(allFlowNodesJson)
		const convertedJson = FlowConverter.yamlToJson(yaml)

		// Verify if multiple conversions can be performed: JSON -> YAML -> JSON -> YAML -> JSON
		// This test ensures the conversion logic can be applied to converted results
		const secondYaml = FlowConverter.jsonToYamlString(convertedJson)
		expect(secondYaml).toBeDefined()

		const secondJson = FlowConverter.yamlToJson(secondYaml)
		expect(secondJson).toBeDefined()

		// Verify key information after second conversion
		expect(secondJson.id).toBe(allFlowNodesJson.id)
		expect(secondJson.name).toBe(allFlowNodesJson.name)
		expect(secondJson.nodes.length).toBe(allFlowNodesJson.nodes.length)
		expect(secondJson.edges.length).toBe(allFlowNodesJson.edges.length)
	}, 30000) // 30 seconds timeout

	it("should be able to handle complex parameter structures in nodes", () => {
		// JSON -> YAML -> JSON
		const yaml = FlowConverter.jsonToYamlString(allFlowNodesJson)
		const convertedJson = FlowConverter.yamlToJson(yaml)

		// Find a node with complex structure for comparison
		const complexNodes = allFlowNodesJson.nodes.filter(
			(n: any) =>
				n.params &&
				((n.params.branches && n.params.branches.length > 0) || // Nodes with branches
					(n.params.model_config && Object.keys(n.params.model_config).length > 0)), // Nodes with model configuration
		)

		if (complexNodes.length > 0) {
			// Test the first complex node
			const originalComplexNode = complexNodes[0]
			const convertedComplexNode = convertedJson.nodes.find(
				(n: any) => n.id === originalComplexNode.id,
			)

			expect(convertedComplexNode).toBeDefined()

			// Check node structure
			// function compareObjects(original: any, converted: any, path = "") {
			// 	// Check object type
			// 	expect(typeof converted).toBe(typeof original)

			// 	if (original === null || typeof original !== "object") {
			// 		expect(converted).toEqual(original)
			// 		return
			// 	}

			// 	// For arrays
			// 	if (Array.isArray(original)) {
			// 		expect(Array.isArray(converted)).toBe(true)
			// 		expect(converted.length).toBe(original.length)
			// 		original.forEach((item, i) => {
			// 			compareObjects(item, converted[i], `${path}[${i}]`)
			// 		})
			// 		return
			// 	}

			// 	// For objects
			// 	const originalKeys = Object.keys(original)
			// 	const convertedKeys = Object.keys(converted)

			// 	// Check key properties
			// 	originalKeys.forEach((key) => {
			// 		// Only compare important properties, ignore edge cases
			// 		if (
			// 			original[key] !== undefined &&
			// 			original[key] !== null &&
			// 			typeof original[key] !== "function"
			// 		) {
			// 			const keyPath = path ? `${path}.${key}` : key

			// 			// For certain specific properties, the structure may differ, but core information should remain consistent
			// 			// For example, when the converted object may have some extra properties or be missing some non-critical properties
			// 			if (convertedKeys.includes(key)) {
			// 				compareObjects(original[key], converted[key], keyPath)
			// 			}
			// 		}
			// 	})
			// }

			// Compare key parts of params structure
			if (originalComplexNode.params.branches) {
				expect(convertedComplexNode.params.branches).toBeDefined()
				expect(convertedComplexNode.params.branches.length).toBe(
					originalComplexNode.params.branches.length,
				)

				// Check key properties of the first branch
				if (originalComplexNode.params.branches.length > 0) {
					const originalBranch = originalComplexNode.params.branches[0]
					const convertedBranch = convertedComplexNode.params.branches[0]

					expect(convertedBranch.branch_id).toBe(originalBranch.branch_id)
					expect(convertedBranch.trigger_type).toBe(originalBranch.trigger_type)
				}
			}

			if (originalComplexNode.params.model_config) {
				expect(convertedComplexNode.params.model_config).toBeDefined()
				// Check key parameters of model configuration
				Object.keys(originalComplexNode.params.model_config).forEach((key) => {
					expect(convertedComplexNode.params.model_config[key]).toBe(
						originalComplexNode.params.model_config[key],
					)
				})
			}
		}
	}, 30000) // 30 seconds timeout
})
