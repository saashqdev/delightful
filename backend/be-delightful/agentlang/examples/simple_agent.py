#!/usr/bin/env python
# -*- coding: utf-8 -*-

"""
Simple AgentLang usage example
"""

import asyncio

from agentlang.event import EventDispatcher
from agentlang.llms import LLMClientConfig, LLMFactory


async def main():
    # Initialize LLM client
    llm_config = LLMClientConfig(
        model="gpt-4.1",
        api_key="your-api-key-here",
    )
    llm_client = LLMFactory.create_llm_client(llm_config)

    # Initialize event dispatcher
    event_dispatcher = EventDispatcher()

    # More example code can be added here

    print("AgentLang example has been initialized")

if __name__ == "__main__":
    asyncio.run(main()) 
