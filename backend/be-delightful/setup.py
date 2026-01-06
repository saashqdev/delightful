#!/usr/bin/env python
# -*- coding: utf-8 -*-

from setuptools import find_packages, setup

with open("README.md", "r", encoding="utf-8") as fh:
    long_description = fh.read()

setup(
    name="super-delightful",
    version="1.0.0",
    author="BeDelightful Team",
    author_email="dev@bedelightful.ai",
    description="BeDelightful General AI System",
    long_description=long_description,
    long_description_content_type="text/markdown",
    url="https://github.com/saashqdev/delightful/super-delightful",
    packages=find_packages(),
    classifiers=[
        "Programming Language :: Python :: 3",
        "License :: OSI Approved :: Apache 2.0 License + Private License",
        "Operating System :: OS Independent",
    ],
    python_requires=">=3.8",
    install_requires=[
        "openai>=1.0.0",
        "python-dotenv>=0.19.0",
        "pydantic>=2.0.0",
        "aiohttp>=3.8.0",
        "numpy>=1.20.0",
        "pandas>=1.3.0",
        "matplotlib>=3.4.0",
        "requests<2.32.0",
        "fastapi>=0.68.0",
        "uvicorn>=0.15.0",
        "docker>=6.1.3,<7.0.0",
        "websockets>=10.0",
        "typer[all]>=0.9.0",
        "watchdog>=2.1.0",
    ],
    entry_points={
        "console_scripts": [
            "super-delightful=app.agent.super_delightful:main",
            "sandbox-gateway=sandbox_gateway.main:start",
            "storage-uploader=app.command.storage_uploader_tool:cli_app"
        ],
    },
)
