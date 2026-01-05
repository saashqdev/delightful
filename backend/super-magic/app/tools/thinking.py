"""
Thinking Tool Module

Provides deep thinking and planning functionality based on Chain-of-Thought (CoT),
helping agents perform reasoning and decision-making.
"""

from typing import Any, Dict, List

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.tools.tool_result import ToolResult
from app.tools.core import BaseTool, BaseToolParams, tool


class ThinkingParams(BaseToolParams):
    """Thinking tool parameters model"""
    problem: str = Field(
        ...,
        description="Problem or challenge to think about, should clearly state the core question"
    )
    thinking: str = Field(
        ...,
        description="Thinking process and analysis of the problem, including background, context, observations and reasoning process"
    )
    steps: List[Dict[str, str]] = Field(
        default=[],
        description="List of thinking steps, each step contains title and detailed reasoning process"
    )
    target: str = Field(
        default="",
        description="Target result of thinking, such as conclusion, solution, action plan or decision recommendation"
    )


@tool()
class Thinking(BaseTool[ThinkingParams]):
    """Thinking tool for performing deep reasoning and planning based on provided context

Perform deep thinking and analysis through Chain-of-Thought (CoT) methodology.

Use this tool to systematically analyze complex problems, make plans or evaluate solutions.
The tool receives your deep thinking process and presents it in a structured manner.

When facing complex problems, please:
1. Clearly define the problem and thinking objective
2. Break down into multiple sub-problems or thinking steps
3. Reason step by step, showing detailed reasoning process and clear intermediate conclusions
4. Consider multiple perspectives, assumptions and constraints
5. Evaluate various possibilities and potential impacts
6. Integrate conclusions from all steps to form final recommendations

Applicable scenarios: complex decision analysis, project planning, root cause analysis of problems,
risk assessment, solution comparison, etc.
    """

    async def execute(self, tool_context: ToolContext, params: ThinkingParams) -> ToolResult:
        """
        Execute thinking process and return result

        Args:
            tool_context: Tool context
            params: Thinking tool parameters

        Returns:
            ToolResult: Tool result containing thinking process and conclusion
        """
        # Build formatted output
        output = []

        # Add problem and thinking process
        output.append(f"About {params.problem}, {params.thinking}")

        # Add steps/flow
        output.append("Process:\\n")
        for i, step in enumerate(params.steps, 1):
            title = step.get("title", f"Step {i}")
            content = step.get("content", "")
            output.append(f"Step {i}: {title}\\n{content}\\n")

        # Add target result
        output.append(f"Therefore, {params.target}")

        # Return result
        return ToolResult(
            name=self.name,
            content="\n".join(output),
        )

    async def get_before_tool_call_friendly_content(self, tool_context: ToolContext, arguments: Dict[str, Any] = None) -> str:
        """
        Get friendly content before tool call
        """
        problem = arguments.get("problem", "") if arguments else ""
        thinking = arguments.get("thinking", "") if arguments else ""

        if problem and thinking:
            return f"Starting deep thinking on problem: {problem}, {thinking}"
        elif problem:
            return f"Starting deep thinking on problem: {problem}"
        else:
            return arguments["explanation"]

    async def get_after_tool_call_friendly_action_and_remark(self, tool_name: str, tool_context: ToolContext, result: ToolResult, execution_time: float, arguments: Dict[str, Any] = None) -> Dict:
        """
        Get friendly action and remark after tool call

        Args:
            tool_name: Tool name
            tool_context: Tool context
            result: Tool execution result
            execution_time: Execution time
            arguments: Execution parameters

        Returns:
            Dict: Dictionary containing action and remark
        """
        problem = ""
        if arguments and "problem" in arguments:
            problem = arguments["problem"]
            # Truncate overly long problem description
            if len(problem) > 100:
                problem = problem[:100] + "..."

        return {
            "action": "Deep thinking analysis",
            "remark": problem
        }
