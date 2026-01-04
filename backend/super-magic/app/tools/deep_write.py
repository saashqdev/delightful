from datetime import datetime
from typing import Any, Dict, List, Optional

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.llms.factory import LLMFactory
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.context.agent_context import AgentContext
from app.core.entity.factory.tool_detail_factory import ToolDetailFactory
from app.core.entity.message.server_message import ToolDetail
from app.core.entity.tool.tool_result import DeepWriteToolResult
from app.tools.core import BaseTool, BaseToolParams, tool
from app.tools.read_file import ReadFile, ReadFileParams

logger = get_logger(__name__)


class DeepWriteParams(BaseToolParams):
    """Parameters for the deep writing tool."""
    query: str = Field(
        ...,
        description="Topic or requirements for deep writing; clearly describe needs, style, target audience, etc."
    )
    reference_files: List[str] = Field(
        ...,
        description="List of reference file paths; at least 3 files are required to ensure accuracy and depth."
    )


@tool()
class DeepWrite(BaseTool[DeepWriteParams]):
    """
    Deep writing tool for generating high-quality, in-depth articles.

    Suitable for (non-exhaustive):
    - Professional articles, reports, and analysis
    - Marketing copy, public-account/blog content
    - Multi-angle, well-reasoned viewpoints
    - Synthesizing references into coherent, professional writing

    Requirements:
    - All content must be grounded in the reference files; no fabrication, no unfounded facts or conclusions
    - At least three reference files to ensure reliability and diversity of perspectives

    Example call:
    ```
    {
        "query": "Use the references to produce a Markdown article about AI in a WeChat-official-account style with insightful depth",
        "reference_files": ["./webview_report/file1.md", "./webview_report/file2.md", "./webview_report/file3.md"]
    }
    ```

    Notes:
    - If references are insufficient to support the task, this tool returns guidance; adjust references or requirements accordingly.
    """

    async def execute(
        self,
        tool_context: ToolContext,
        params: DeepWriteParams
    ) -> ToolResult:
        """Perform deep writing and return the formatted result.

        Two passes ensure quality and factual correctness:
        1) Generate initial content from references
        2) Fact-check and refine
        """
        try:
            # Extract inputs
            query = params.query
            reference_files = params.reference_files or []

            # Ensure enough references
            if len(reference_files) < 3:
                error_msg = (
                    "At least three reference files are required to ensure reliable, well-sourced writing. "
                    "Use the list_dir tool to gather enough references before deep writing."
                )
                logger.warning(f"Deep writing aborted: {error_msg} (provided {len(reference_files)} files)")
                return DeepWriteToolResult(error=error_msg)

            # Fixed defaults; may move back to params later
            temperature = 0.7
            model_id = "deepseek-reasoner"

            # Record deep writing request
            logger.info(f"Deep writing: query={query}, files={len(reference_files)}, model={model_id}")

            # Process reference files
            reference_materials = ""
            if reference_files:
                reference_materials = await self._process_reference_files(tool_context, reference_files)

            # Get and format current time context
            weekday_names = ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]
            current_time_str = datetime.now().strftime(f"%Y-%m-%d %H:%M:%S {weekday_names[datetime.now().weekday()]} (Week %W)")

            # context_info includes the first separator
            context_info = f"Context information:\nCurrent time: {current_time_str}\n\n---\n\n"

            # Prompt enforcing strict sourcing
            prompt_text = (
                "Base all output strictly on the reference materials. Do NOT fabricate information, quotes, or facts. "
                "Every statement must include a citation to the references. References contain metadata with titles and sources; "
                "cite them as Markdown links, e.g., source: [Title](URL)."
            )

            # Build query parts
            query_parts = []

            # Add leading prompt
            query_parts.append(prompt_text + "\n\n---\n\n")

            # Add context info
            query_parts.append(context_info)

            # Add references and separator
            if reference_materials:
                query_parts.append(f"Reference materials (use fully):\n{reference_materials}\n\n---\n\n")

            # Add user request
            query_parts.append(f"User request: {query}")

            # Re-emphasize at the end
            query_parts.append(
                "\n\n---\n\nEmphasis: " + prompt_text +
                " Think step by step to ensure every part is grounded in the provided references before finalizing your deep writing."
            )

            full_query = "".join(query_parts) # Join all parts

            # Build messages (deepseek-reasoner lacks system prompts; keep everything in user content)
            messages = [
                {
                    "role": "user",
                    "content": full_query
                }
            ]

            # First pass: generate deep writing
            logger.info("Starting first deep-writing pass...")
            response = await LLMFactory.call_with_tool_support(
                model_id=model_id,
                messages=messages,
                tools=None,  # No tool support needed
                stop=None,
                agent_context=tool_context.get_extension_typed("agent_context", AgentContext)
            )

            # Handle response
            if not response or not response.choices or len(response.choices) == 0:
                return DeepWriteToolResult(error="No valid response received from model")

            # Extract model content
            message = response.choices[0].message
            first_round_reasoning_content = getattr(message, "reasoning_content", "")
            # If the model returns no reasoning, use empty string
            if not first_round_reasoning_content:
                first_round_reasoning_content = ""
            first_round_content = message.content

            # Second pass: fact-check
            logger.info("Starting second reflective pass...")

            # Place system guidance inside user prompt
            system_content = "You are a strict fact-checker ensuring content is fully grounded in the references with no fabrication."

            reflection_query = f"""{system_content}

Carefully verify that the following content is fully based on the references with no fabricated or unsubstantiated information, then output the corrected final content.

References:
{reference_materials}

First-pass content:
{first_round_content}

Compare the references to the generated content and check for fabricated parts:
1. If entirely based on references, return it as-is.
2. If minor fabrication exists, remove and correct it.
3. If major fabrication exists, rewrite entirely based on the references.
4. If references are insufficient, state the insufficiency clearly; do not invent content.

Output the final corrected content or an insufficiency notice. The first-pass content is not shown to the user, so your output must be complete and final. Do not give suggestions only; provide the full final content.
"""

            reflection_messages = [
                {
                    "role": "user",
                    "content": reflection_query
                }
            ]

            reflection_response = await LLMFactory.call_with_tool_support(
                model_id=model_id,
                messages=reflection_messages,
                tools=None,
                stop=None,
                agent_context=tool_context.get_extension_typed("agent_context", AgentContext)
            )

            if not reflection_response or not reflection_response.choices or len(reflection_response.choices) == 0:
                # Fall back to first pass
                logger.warning("Reflection pass failed; returning first-pass result")
                result = DeepWriteToolResult(content=first_round_content)
                if first_round_reasoning_content:
                    result.set_reasoning_content(first_round_reasoning_content)
                return result

            # Handle second-pass result
            reflection_message = reflection_response.choices[0].message
            reflection_content = reflection_message.content

            # Use reflection result as final content
            final_content = reflection_content

            # Build result
            result = DeepWriteToolResult(content=final_content)

            # Combine first-pass reasoning and reflection into one trace
            combined_reasoning = ""
            if first_round_reasoning_content:
                combined_reasoning += f"[First deep-writing pass]\n{first_round_reasoning_content}\n\n"
            combined_reasoning += f"[Reflection]\n{reflection_content}"

            result.set_reasoning_content(combined_reasoning)

            return result

        except Exception as e:
            logger.exception(f"Deep writing failed: {e!s}")
            return DeepWriteToolResult(error=f"Deep writing failed: {e!s}")

    async def get_tool_detail(self, tool_context: ToolContext, result: ToolResult, arguments: Dict[str, Any] = None) -> Optional[ToolDetail]:
        """
        Build tool detail for frontend display.
        """
        if not result.content:
            return None

        try:
            if not isinstance(result, DeepWriteToolResult):
                return None

            if not result.ok:
                return None

            # Return structured data for UI
            return ToolDetailFactory.create_deep_write_detail(
                title="Deep Writing Result",
                reasoning_content=result.reasoning_content or "No deep-writing trace",
                content=result.content
            )
        except Exception as e:
            logger.error(f"Failed to build tool detail: {e!s}")
            return None

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """Provide friendly action/remark summary after execution."""
        if not arguments or "query" not in arguments:
            return {
                "action": "Deep writing",
                "remark": "Deep writing completed"
            }

        query = arguments["query"]
        # Trim long queries for remark
        short_query = query[:100] + "..." if len(query) > 100 else query
        return {
            "action": "Deep writing",
            "remark": f"Deep writing topic: {short_query}"
        }

    async def _process_reference_files(self, tool_context: ToolContext, reference_files: List[str]) -> str:
        """Read and format reference files using the ReadFile tool."""
        reference_content = []
        read_file_tool = ReadFile()

        for i, file_path in enumerate(reference_files, 1):
            try:
                # Read file content via ReadFile tool
                read_file_params = ReadFileParams(
                    file_path=file_path,
                    should_read_entire_file=True
                )

                # @FIXME: invoke without relying on tool_context
                result = await read_file_tool.execute(tool_context, read_file_params)

                if result.ok:
                    content = result.content
                    # Extract filename
                    file_name = file_path.split('/')[-1]
                    reference_content.append(f"[File {i}: {file_name}]\n{content}")
                else:
                    logger.error(f"Failed to read reference file {file_path}: {result.content}")
                    reference_content.append(f"[File {i}: {file_path}] read failed: {result.content}")
            except Exception as e:
                logger.error(f"Exception while processing reference file {file_path}: {e}")
                reference_content.append(f"[File {i}: {file_path}] exception: {e!s}")

        # Join all references with separators
        if reference_content:
            return "\n\n---\n\n".join(reference_content)
        return ""
