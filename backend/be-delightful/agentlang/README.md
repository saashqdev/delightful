<div align="center">

# AgentLang

**Language-first AI Agent Framework**
*Building AI Agents with Natural Language at the Core*

[![Python Version](https://img.shields.io/badge/python-3.8%20%7C%203.9%20%7C%203.10%20%7C%203.11%20%7C%203.12-blue)](https://www.python.org)
[![License](https://img.shields.io/badge/license-Apache%202.0-green)](LICENSE)
[![Latest Stable Version](http://poser.pugx.org/dtyq/agentlang/v)](https://packagist.org/packages/dtyq/agentlang)
[![Status](https://img.shields.io/badge/status-beta-yellow)](https://github.com/saashqdev/delightful/agentlang)
[![Made with ❤️](https://img.shields.io/badge/Made%20with-❤️-orange.svg)](https://github.com/saashqdev/delightful/agentlang)

</div>

## 📖 Introduction

AgentLang is a revolutionary AI agent framework that places the "**natural language first**" concept at the core of its design. Unlike traditional agent frameworks dominated by code structures and engineering paradigms, AgentLang uses natural language as the core driving force, enabling developers to define, configure, and control agent behavior through natural language.

In AgentLang, language is not only a medium for input and output, but also the primary form of expressing agent behavior logic. This paradigm shift makes AI agent development more intuitive, efficient, and flexible, allowing non-professional developers to easily create complex agent systems.

By simplifying key features such as LLM integration, tool invocation, session management, and event handling, AgentLang significantly lowers the technical barrier required to build complex AI agents, allowing you to focus on agent behavior logic and user value.

## ✨ Core Features

### 🔤 Language-First Architecture
- **Natural Language Configuration**: Define agent behavior and capabilities directly through natural language descriptions
- **Semantic-Driven Development**: Agent behavior logic is primarily implemented through semantic descriptions rather than traditional coding
- **Dynamic Instruction System**: Flexible runtime instruction mechanism for adjusting agent behavior
- **Context-Aware Capabilities**: Deep understanding of conversation context, maintaining long-term consistency

### 🧠 Agent System
- **Flexible Agent Architecture**: Supports various agent patterns including conversational, task-based, and hybrid
- **Context Management**: Intelligent session context management, maintaining agent memory and state
- **Adaptive Logic**: Agents can adjust their behavior and response strategies based on context

### 🔌 LLM Integration
- **Unified Interface**: Encapsulates API differences across LLM providers, providing a unified calling interface
- **Multi-Model Support**: Native support for OpenAI GPT series, easily extensible to other models
- **Token Management**: Built-in token counting and optimization features to help control costs

### 🛠️ Tools & Plugins
- **Semantic Tool Definition**: Define tool functions and parameters through natural language descriptions
- **Tool Call Tracking**: Comprehensive recording of tool calls and responses for debugging and auditing
- **Rich Extension Points**: Inject custom logic at various stages of the agent workflow

### ⚡ Event-Driven Architecture
- **Event Bus**: Event system based on publish/subscribe pattern, achieving loose coupling between components
- **Middleware Support**: Add middleware to process events for logging, monitoring, or interception
- **Extensible Event Types**: Support for custom event types and their processing logic

### 📝 Conversation Management
- **Session Persistence**: Automatically save and restore conversation history
- **Intelligent Summarization**: Automatic compression and summarization of long conversations
- **Session Analysis**: Provides conversation data analysis and visualization tools

## 📐 Architectural Philosophy

AgentLang's "natural language first" design philosophy is reflected in the following aspects:

1. **Language as Interface**: Natural language as the primary interface for human-computer interaction, without complex GUIs or dedicated DSLs
2. **Language as Configuration**: Configure agent behavior through natural language descriptions, reducing tedious configuration files
3. **Language as Logic**: Agent decision logic is primarily implemented through the reasoning capabilities of language models
4. **Language as Extension**: New features can be defined and integrated through language descriptions

This design makes AgentLang particularly suitable for:
- **Cross-Domain Experts**: Create professional domain agents without deep programming background
- **Rapid Prototyping**: Shorten the time from concept to prototype
- **Adaptive Systems**: Quickly adapt to new requirements and scenario changes

## 🧩 Project Structure

AgentLang's code organization follows a clear modular structure:

- **agent** - Contains core agent implementation, handling agent lifecycle and behavior
- **llms** - Manages integration and interaction with various large language models
- **tools** - Defines tool interfaces and provides common tool implementations
- **event** - Implements event-driven system, enabling communication between components through events
- **chat_history** - Manages storage, retrieval, and optimization of conversation history
- **context** - Provides context management capabilities, including global and session-level contexts
- **interface** - Defines interfaces and protocols between system components
- **utils** - Contains various auxiliary functions and utility tools

## 📄 License

This project is licensed under the Apache License 2.0. See the [LICENSE](LICENSE) file for details.

## 📞 Contact

- **Email**: dev@bedelightful.ai
- **GitHub**: [https://github.com/saashqdev/delightful/agentlang](https://github.com/saashqdev/delightful/agentlang)
- **Issue Tracker**: [https://github.com/saashqdev/delightful/agentlang/issues](https://github.com/saashqdev/delightful/agentlang/issues)

---

<div align="center">
<b>AgentLang: Making Natural Language the Core of AI Agent Development</b>

Developed with ❤️ by the BeDelightful Team
</div> 