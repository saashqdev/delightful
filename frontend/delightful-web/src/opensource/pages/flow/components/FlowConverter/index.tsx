// @ts-nocheck
import React, { useState } from "react"
import { Button, Card, Input, Select, message, Spin, Space, Typography, Tabs } from "antd"
import { IconArrowRight } from "@tabler/icons-react"
import {
	convertYAMLToJSON,
	convertJSONToYAML,
	convertJSONStringToYAML,
	convertYAMLToJSONString,
} from "../../utils/api/flowConverter"
import styles from "./index.module.less"

const { TextArea } = Input
const { Text, Title } = Typography
const { TabPane } = Tabs

/**
 * Flow Converter Component
 * Provides UI for converting between YAML and JSON formats
 */
const FlowConverter: React.FC = () => {
	const [sourceText, setSourceText] = useState<string>("")
	const [resultText, setResultText] = useState<string>("")
	const [conversionType, setConversionType] = useState<string>("yaml2json")
	const [loading, setLoading] = useState<boolean>(false)
	const [activeTab, setActiveTab] = useState<string>("converter")

	// Handle file upload
	const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
		const file = e.target.files?.[0]
		if (!file) return

		const reader = new FileReader()
		reader.onload = (event) => {
			const content = event.target?.result as string
			setSourceText(content)
		}
		reader.readAsText(file)
	}

	// Download result
	const handleDownload = () => {
		if (!resultText) {
			message.warning("No content to download")
			return
		}

		const element = document.createElement("a")
		const fileType = conversionType.includes("yaml2json") ? "json" : "yml"
		const file = new Blob([resultText], { type: "text/plain" })
		element.href = URL.createObjectURL(file)
		element.download = `converted.${fileType}`
		document.body.appendChild(element)
		element.click()
		document.body.removeChild(element)
	}

	// Execute conversion
	const handleConvert = () => {
		if (!sourceText) {
			message.warning("Please enter content to convert")
			return
		}

		setLoading(true)
		try {
			let result

			switch (conversionType) {
				case "yaml2json":
					result = convertYAMLToJSONString(sourceText)
					break
				case "json2yaml":
					result = convertJSONStringToYAML(sourceText)
					break
				default:
					result = null
			}

			if (result) {
				setResultText(result)
				message.success("Conversion successful")
			} else {
				message.error("Conversion failed")
			}
		} catch (error) {
			console.error("Conversion error:", error)
			message.error(`Conversion failed: ${error.message}`)
		} finally {
			setLoading(false)
		}
	}

	// Clear content
	const handleClear = () => {
		setSourceText("")
		setResultText("")
	}

	return (
		<div className={styles.flowConverter}>
			<Tabs activeKey={activeTab} onChange={setActiveTab}>
				<TabPane tab="Flow Converter" key="converter">
					<Card>
						<Title level={4}>Flow YAML and JSON Converter</Title>
						<Text>Convert Flow workflows between YAML and JSON formats</Text>

						<div className={styles.conversionTypeSelector}>
							<Select
								value={conversionType}
								onChange={setConversionType}
								options={[
									{ value: "yaml2json", label: "YAML to JSON" },
									{ value: "json2yaml", label: "JSON to YAML" },
								]}
								style={{ width: 200 }}
							/>
						</div>

						<div className={styles.converterContainer}>
							<div className={styles.sourceContainer}>
								<Card title="Source File" bordered={false}>
									<TextArea
										value={sourceText}
										onChange={(e) => setSourceText(e.target.value)}
										placeholder={
											conversionType === "yaml2json"
											? "Please enter YAML formatted Flow"
											: "Please enter JSON formatted Flow"
										}
										rows={20}
									/>
									<div className={styles.actions}>
										<Space>
											<input
												type="file"
												id="file-upload"
												accept={
													conversionType === "yaml2json"
														? ".yml,.yaml"
														: ".json"
												}
												onChange={handleFileUpload}
												style={{ display: "none" }}
											/>
											<Button
												onClick={() =>
													document.getElementById("file-upload")?.click()
												}
											>
												Upload File
											</Button>
											<Button onClick={handleClear}>Clear</Button>
										</Space>
									</div>
								</Card>
							</div>

							<div className={styles.convertButton}>
								<Button
									type="primary"
									icon={<IconArrowRight />}
									onClick={handleConvert}
									loading={loading}
								>
									Convert
								</Button>
							</div>

							<div className={styles.resultContainer}>
								<Card title="Conversion Result" bordered={false}>
									<Spin spinning={loading}>
										<TextArea value={resultText} readOnly rows={20} />
									</Spin>
									<div className={styles.actions}>
										<Button
											type="primary"
											onClick={handleDownload}
											disabled={!resultText}
										>
											Download
										</Button>
									</div>
								</Card>
							</div>
						</div>
					</Card>
				</TabPane>

				<TabPane tab="Help" key="help">
					<Card>
						<Title level={4}>Flow Converter Usage</Title>
						<Title level={5}>YAML to JSON</Title>
						<Text>
							Convert Flow workflows from YAML format to JSON format.
							<ul>
								<li>Paste YAML content in the source file area, or upload a YAML file</li>
								<li>Click the &quot;Convert&quot; button to execute the conversion</li>
								<li>After conversion, download or copy the result</li>
							</ul>
						</Text>

						<Title level={5}>JSON to YAML</Title>
						<Text>
							Convert Flow JSON format to YAML format.
							<ul>
								<li>Paste JSON content in the source file area, or upload a JSON file</li>
								<li>Click the &quot;Convert&quot; button to execute the conversion</li>
								<li>After conversion, download or copy the result</li>
							</ul>
						</Text>

						<Title level={5}>YAML Format Description</Title>
						<Text>
							<pre
								style={{
									backgroundColor: "#f5f5f5",
									padding: "10px",
									borderRadius: "5px",
								}}
							>
								{`# Flow YAML Format Example
flow:
  name: "Flow Name"
  description: "Flow Description"
  version: "1.0.0"
  type: "workflow"

variables:
  - name: "variable_name"
    type: "string"
    default: "default_value"

nodes:
  - id: "node-123"
    type: "start"
    name: "Start Node"
    position:
      x: 100
      y: 100
    params:
      # Node parameters...

edges:
  - id: "edge-123"
    source: "node-123"
    target: "node-456"
`}
							</pre>
						</Text>
					</Card>
				</TabPane>
			</Tabs>
		</div>
	)
}

export default FlowConverter
