# Delightful Flow Foundation Package

<div align="center">
  <img src="https://img.shields.io/badge/Status-Developing-blue" alt="Status: In Development">
  <img src="https://img.shields.io/badge/Framework-React-61dafb" alt="Framework: React">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License: MIT">
</div>

## 📖 Project Overview

Delightful Flow Foundation Package is an out-of-the-box flow library extracted from Delightful Flow. It is built on ReactFlow, providing powerful flowchart design and management capabilities. The project bundles the core flow package, JSON Schema editor, expression components, and shared UI components to accelerate visual flow application development.

## ✨ Core Features

- 🔄 Flowchart design and management based on ReactFlow
- 🎯 High-performance node and edge handling (batching and debounce optimizations)
- 🧩 Extensible node type system
- 🔍 JSON Schema form editing capabilities
- 🌐 Multilingual support
- 🎨 Polished and customizable UI components

## 📦 Installation

```bash
# Install dependency
npm install @delightful/delightful-flow
```

## 📚 Usage Guide

There is currently no single quick-start guide. To use the components, refer to these resources:

- Check the `index.md` file in each component directory for detailed instructions
- Review the sample projects in the `examples` directory for real-world scenarios
- Each component includes example code for development reference

For example, to learn how to use `DelightfulFlow`:
1. Open `src/DelightfulFlow/index.md`
2. Review the sample projects in `examples/DelightfulFlow`

## 📚 API Docs

### Main Components

- `DelightfulFlow`: Flow designer core component
- `DelightfulJsonSchemaEditor`: Schema-driven form generator
- `DelightfulExpressionWidget`: Expression builder and editor
- `DelightfulConditionEdit`: Condition editing component

### Core Hooks

- `useBaseFlow`: Core flow logic hook that manages nodes and edges
- `useNodeBatchProcessing`: Batch processing hook for high-volume node rendering performance

### Detailed Docs and Examples

- Each component ships with detailed usage docs in its `index.md`
- `DelightfulFlow` includes rich real-world examples in the `examples` directory
- Samples demonstrate the flow designer across scenarios like node customization and form configuration

## 🛠️ Development

```bash
# Install dependencies
npm install

# Start the documentation demo for development
npm start

# Build the library
npm run build
```

## 🤝 Contribution Guide

Contributions and issues are welcome! Please fork the repo first, then open a Pull Request.

## 📄 License

MIT

---

# Delightful Flow Foundation Package

<div align="center">
  <img src="https://img.shields.io/badge/Status-Development-blue" alt="Status: Development">
  <img src="https://img.shields.io/badge/Framework-React-61dafb" alt="Framework: React">
  <img src="https://img.shields.io/badge/License-MIT-green" alt="License: MIT">
</div>

## 📖 Project Introduction

Delightful Flow Foundation Package is an out-of-the-box flow foundation library extracted from Delightful Flow, encapsulated based on ReactFlow, providing powerful flow chart design and management capabilities. This project integrates flow foundation components, JSON Schema editor, expression components, and internal common UI components to help quickly build visual flow applications.

## ✨ Core Features

- 🔄 Flow chart design and management based on ReactFlow
- 🎯 High-performance node and edge handling (batch processing and debounce optimization)
- 🧩 Extensible node type system
- 🔍 JSON Schema form editing capabilities
- 🌐 Multilingual support
- 🎨 Beautiful and customizable UI components

## 📦 Installation

```bash
# Install dependencies
npm install @delightful/delightful-flow
```

## 📚 Usage Guide

Currently, there is no unified quick start guide. To use the components, please refer to the following resources:

- Check the `index.md` file in each component directory for detailed instructions on that component
- Refer to the sample projects in the `examples` directory to understand actual application scenarios
- Each component has corresponding sample code that can serve as a development reference

For example, to learn how to use the `DelightfulFlow` component, you can:
1. Check the `src/DelightfulFlow/index.md` file
2. Refer to the sample projects in the `examples/DelightfulFlow` directory

## 📚 API Documentation

### Main Components

- `DelightfulFlow`: Flow designer main component
- `DelightfulJsonSchemaEditor`: Form generator based on Schema
- `DelightfulExpressionWidget`: Expression building and editing component
- `DelightfulConditionEdit`: Condition editing component

### Core Hooks

- `useBaseFlow`: Core flow logic hook, managing node and connection states
- `useNodeBatchProcessing`: Node batch processing hook, improving rendering performance for a large number of nodes

### Detailed Documentation and Examples

- Each component comes with detailed usage documentation. Please refer to the `index.md` file in the component directory for specific usage
- The `DelightfulFlow` component provides rich actual development cases. Check the sample projects in the `examples` directory
- The sample projects demonstrate the practical application of the flow designer in different scenarios, including node customization, form configuration, etc.

## 🛠️ Development

```bash
# Install dependencies
npm install

# Start the documentation demo for development
npm start

# Build library code
npm run build
```

## 🤝 Contribution Guide

Contributions of code or issues are welcome! Please fork this repository first, then submit a Pull Request.

## 📄 License

MIT
