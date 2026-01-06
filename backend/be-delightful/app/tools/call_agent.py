from typing import Any, Dict, List

from pydantic import Field

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from agentlang.utils.file import get_file_info
from app.tools.abstract_file_tool import AbstractFileTool
from app.tools.core import BaseToolParams, tool

logger = get_logger(__name__)


class CallAgentParams(BaseToolParams):
    """Call agent parameters"""
    agent_name: str = Field(
        ...,
        description="The name of the agent to call"
    )
    agent_id: str = Field(
        ...,
        description="Unique identifier for this task, human-readable and distinctive, must not be repeated, composed of words or phrases, e.g. 'bedelightful-ai-background-research'"
    )
    task_background: str = Field(
        ...,
        description="User's original requirements and background information (comprehensive lossless background information summary), provide the most original situation from user or context and current global situation description, avoid fatal information gaps that cause sub-Agent to do things beyond requirements. You need to tirelessly explain this background information to each agent, ensure each agent fully understands background information, avoid information gaps. Must be at least 300 words."
    )
    task_description: str = Field(
        ...,
        description="Task description (what the called agent needs to do), focus is on the specific description of the highly decomposed task that the called agent is responsible for, not the overall task description, description should be simple and precise enough, avoid the called agent doing things beyond requirements or spending too much time. Must be at least 200 words."
    )
    task_completion_standard: str = Field(
        ...,
        description="Task completion and acceptance criteria (how to determine it's done), needs to be quantified, such as producing a beautifully formatted HTML file named XXX. Must be at least 100 words."
    )
    reference_files: List[str] = Field(
        ...,
        description="List of reference file paths, containing files valuable for the task, such as ['./webview_reports/foo.md', './webview_reports/bar.md']. These files will serve as background materials or reference basis for the task, ensuring the agent fully understands and better completes the task."
    )

@tool()
class CallAgent(AbstractFileTool[CallAgentParams]):
    """
    Call other agents to complete tasks.
    Each call should have a small enough and clear enough goal to allow the agent to complete the task in the most efficient way.
    """

    def get_prompt_hint(self) -> str:
        """Generate XML-formatted prompt information with detailed tool usage instructions"""
        hint = """<tool name="call_agent">
  <examples>
    <![CDATA[[The following are usage examples of the call_agent tool, you need to strictly follow the examples to make call_agent calls.]]>
    <example>
      <![CDATA[
call_agent(
  agent_name: "web-browser",  # Or other agent suitable for web browsing and file download tasks
  agent_id: "aiga-pdf-download",
  task_background: "The user's original request is: 'Open the https://educators.aiga.org/aiga-designer-2025/ website, find and download the PDF file from 'AIGA Design 2025 Summary Document', then convert the downloaded PDF to Markdown format'. This is fairly clear. Since you are a professional web browsing expert, skilled at collecting, organizing and analyzing information through browser operations, I have decided to delegate this task entirely to you. I will check your completion afterwards and deliver to the user after acceptance.",
  task_description: "You will be fully responsible for most of the user's original request. You need to: 1) Use the browser to open https://educators.aiga.org/aiga-designer-2025/ webpage, find the 'AIGA Design 2025 Summary Document' PDF file download link on the page, and download the PDF file; 2) Convert the downloaded PDF file to Markdown format",
  task_completion_standard: "Successfully download the PDF file and convert it to Markdown format text file, preserving the main content and structure of the original document",
  reference_files: []  # No reference files needed
)
      ]]>
    </example>
    <example>
      <![CDATA[
call_agent(
  agent_name: "data-analyst",  # Data analyst agent
  agent_id: "market-research-analysis",
  task_background: "The user is a market research manager at a startup preparing a presentation on electric vehicle market trends. They have collected some market data (stored in multiple CSV and Excel files), you can see them in the workspace using list_dir. They need to extract key insights from this data and generate a detailed analysis report. The original user requirement is: 'Analyze the growth trends in the EV market from 2020-2024, changes in consumer purchasing behavior, major competitors' market share, and generate a detailed report with data visualization'. We have done preliminary processing of the data files, now need to conduct in-depth analysis and generate the report.",
  task_description: "You are responsible for the most important part of this. You need to use data from the provided reference files: 1) Analyze EV sales growth trends from 2020-2024; 2) Identify purchasing behavior changes across different consumer groups; 3) Calculate and compare major competitors' market share evolution; 4) Generate a detailed analysis report named 'ev_market_analysis.md' containing key findings, data charts and predictive recommendations.",
  task_completion_standard: "Successfully generate an analysis report named 'ev_market_analysis.md' which must include: 1) Sales trend analysis (with at least 2 charts); 2) Consumer behavior analysis (with segmented market data); 3) Competitor analysis (with market share comparison); 4) Market forecast for the next 12 months; 5) 3-5 specific marketing recommendations based on the data.",
  reference_files: [
    "./ev_sales_data_2020_2023.csv",  # Electric vehicle sales data
    "./consumer_survey_results.xlsx",  # Consumer survey results
    "./competitor_analysis.xlsx",  # Competitor analysis
    "./industry_overview.md"  # Industry overview report
  ]
)
      ]]>
    </example>
    <example>
      <![CDATA[
call_agent(
  agent_name: "web-browser",  # First call web browser agent
  agent_id: "conference-info-collector",
  task_background: "The original user requirement is: 'Open https://mp.weixin.qq.com/s/gYjV6gjFutI6afGcpNel6Q, analyze all content in the linked article and form a well-structured document, further analyze each presentation topic and guest speaker information, and finally plan how to better allocate time and make choices in these sessions, as an attendee how to maximize my conference value, and ultimately create a navigation website.' The entire task needs to be completed in two steps: first collect information through web browsing, then build the navigation website through a code agent. We now need to complete the first step of collecting and analyzing information.",
  task_description: "You are responsible for the first phase of the task: 1) Use browser to open and access the https://mp.weixin.qq.com/s/gYjV6gjFutI6afGcpNel6Q link; 2) Extract and analyze all conference content on the page, including presentation topics, guest speaker information, time schedule, etc.; 3) Organize all information to generate a well-structured document with filename 'conference_analysis.md'; 4) Conduct value analysis for each presentation topic and speaker, annotate potential importance and participation value; 5) Propose time allocation recommendations based on analysis to help users as attendees maximize conference value.",
  task_completion_standard: "Successfully generate an analysis document named 'conference_analysis.md' which must include: 1) Complete conference overview; 2) Detailed information of all presentation topics and guest speakers (at minimum including name, position, presentation topic, time schedule); 3) Value analysis for each topic; 4) Time planning recommendations; 5) Strategy for attendees to maximize conference value. Document structure is clear and easy to use for creating the navigation website in next phase.",
  reference_files: []  # No reference files needed, but will generate a reference_file for phase two
)

# After phase one completion, call coder agent to build navigation website
call_agent(
  agent_name: "coder",  # Code developer agent
  agent_id: "conference-navigator-builder",
  task_background: "The original user requirement is 'Open https://mp.weixin.qq.com/s/gYjV6gjFutI6afGcpNel6Q, analyze all content in the linked article and form a well-structured document, further analyze each presentation topic and guest speaker information, and finally plan how to better allocate time and make choices in these sessions, as an attendee how to maximize my conference value, and ultimately create a navigation website.' In phase one we have completed the collection and analysis of conference information, generated documents 'conference_info.md', 'conference_schedule.md', 'speakers_info.md', which you can view in the .webview_reports directory in the workspace. Now we need to create a navigation website based on these documents to help users better plan their conference participation.",
  task_description: "You are responsible for the final and most important phase of the task: 1) Read documents generated in phase one, understand the full structure and content of the conference; 2) Based on your rich development and design experience and preset requirements, develop a navigation website; 3) The website should help users better plan their conference participation and achieve the goal of maximizing conference value; 4) Include key content such as conference overview, schedule, speaker information, topic categories, and personalized recommended itineraries; 5) Provide reasonable interactive functions to help users make conference participation choices and time allocation decisions.",
  task_completion_standard: "Successfully generate a navigation website with 'index.html' as entry file, the website must meet the following standards: 1) Beautiful design using modern UI framework; 2) Complete display of all conference information; 3) Must include four functional modules: conference overview, schedule, speaker information and personalized recommended itineraries; 4) Implement time conflict detection and personalized recommendation functions; 5) Provide filtering and search functions to help users quickly locate interested topics and speakers; 6) Responsive design supporting mobile device access; 7) PDF export function for users to view offline; 8) Include concise operation guide ensuring it can be opened and used directly in any browser environment. The key point is to avoid overly complex projects, you need to use the simplest and most efficient combination to complete this task as quickly as possible.",
  reference_files: [
    "./webview_reports/conference_info.md",  # Conference overall information document
    "./webview_reports/conference_schedule.md",  # Conference schedule document
    "./webview_reports/speakers_info.md"  # Presentation guest speaker information document
  ]
)
      ]]>
    </example>
    <example>
      <![CDATA[
# Step 1: Use web-browser agent to collect AI news hotspots and extract keywords
call_agent(
  agent_name: "web-browser",
  agent_id: "ai-news-collector",
  task_background: "The original user requirement is: 'You can check the recent hot AI news through https://ai-bot.cn/daily-ai-news/, but the content inside is not detailed. Therefore, you can extract search keywords for various hot events from the news titles (noun extraction for events or new things should be accurate), then select a few events you think are most valuable, search for articles related to this event in WeChat official accounts, then take links to articles you think are most valuable, then access WeChat article links through browser to get complete Markdown content of articles, then search search engines for reports about this event from 36Kr, Huxiu and other media, then deeply understand the content of these articles, understand what's happening in the AI circle recently, finally answer an ultimate question 'In the context of Cursor, Manus, Genspark, Devin and other various Agent products competing fiercely, what opportunities are there for startup teams to do enterprise-level AI products?', finally output your unique perspective as a report in the form of a beautiful web page.' This is a complex multi-stage task, we need to first collect information, then analyze perspectives, and finally create a web page. Now we need to first complete the information collection work of the first stage.",
  task_description: "You need to complete the core tasks of the first stage: 1) Visit https://ai-bot.cn/daily-ai-news/ website and check recent days' AI news hotspots; 2) Extract 1-3 most valuable and influential hot event keywords from news titles, keywords should accurately represent events or new technology names; 3) Provide brief explanation for each keyword, explaining why you selected this hotspot; 4) Organize your findings into a document named 'ai_news_hotspots.md', which will serve as the basis for subsequent in-depth research; 5) Record which platforms (such as WeChat official accounts, 36Kr, Huxiu, etc.) may have detailed reports on each hot event.",
  task_completion_standard: "Successfully generate an analysis document named 'ai_news_hotspots.md' for hot events, the document must include: 1) 5-8 most valuable AI hot event keywords; 2) Brief background explanation for each keyword (why important/influential); 3) Recommended search platforms for each keyword (such as specific WeChat official accounts, media websites, etc.); 4) Preliminary value assessment for each hotspot (significance for AI industry development). Keyword extraction must be accurate, usable for subsequent in-depth searches.",
  reference_files: []  # No reference files needed
)

# Step 2: Use web-browser agent to conduct in-depth research on selected hot events (this step may require multiple calls, each targeting different hot events)
call_agent(
  agent_name: "web-browser",
  agent_id: "ai-news-researcher",
  task_background: "The original user requirement is: 'You can check the recent hot AI news through https://ai-bot.cn/daily-ai-news/, but the content inside is not detailed. Therefore, you can extract search keywords for various hot events from the news titles (noun extraction for events or new things should be accurate), then select a few events you think are most valuable, search for articles related to this event in WeChat official accounts, then take links to articles you think are most valuable, then access WeChat article links through browser to get complete Markdown content of articles, then search search engines for reports about this event from 36Kr, Huxiu and other media, then deeply understand the content of these articles, understand what's happening in the AI circle recently, finally answer an ultimate question \"In the context of Cursor, Manus, Genspark, Devin and other various Agent products competing fiercely, what opportunities are there for startup teams to do enterprise-level AI products?\", finally output your unique perspective as a report in the form of a beautiful web page.' We have already identified several important AI hot events and keywords in stage one, now we need to conduct in-depth research on these hotspots and collect detailed information.",
  task_description: "You need to conduct in-depth research on the hot events identified in the previous stage: 1) Check the 'ai_news_hotspots.md' file and select 1 most valuable hot event from it; 2) For the selected hotspot, search for at least 3 high-quality related articles on WeChat official accounts, visit and obtain their complete Markdown content; 3) Simultaneously search for reports about each hotspot from tech media like 36Kr, Huxiu, etc. Since these are new hotspots, media reports may not be available, you can skip if none are found; 4) Return all collected article content to the user.",
  task_completion_standard: "Successfully collect detailed information for the selected hot event, the documentation must include: 1) Complete content of at least 2 WeChat official account articles; 2) At least 2 reporting content from media like 36Kr, Huxiu, etc. (if available); 3) All content must maintain original structure and format; 4) No need for value analysis, only responsible for information collection; 5) Return all collected file paths together.",
  reference_files: [
    "./ai_news_hotspots.md"  # Hot event documentation generated in stage one
  ]
)

# Step 3: Use writer agent to analyze all collected information and answer core questions about AI startup opportunities
call_agent(
  agent_name: "writer",
  agent_id: "ai-industry-analyst",
  task_background: "The user's original requirement ultimately seeks to answer: 'You can check the recent hot AI news through https://ai-bot.cn/daily-ai-news/, but the content inside is not detailed. Therefore, you can extract search keywords for various hot events from the news titles (noun extraction for events or new things should be accurate), then select a few events you think are most valuable, search for articles related to this event in WeChat official accounts, then take links to articles you think are most valuable, then access WeChat article links through browser to get complete Markdown content of articles, then search search engines for reports about this event from 36Kr, Huxiu and other media, then deeply understand the content of these articles, understand what's happening in the AI circle recently, finally answer an ultimate question \"In the context of Cursor, Manus, Genspark, Devin and other various Agent products competing fiercely, what opportunities are there for startup teams to do enterprise-level AI products?\", finally output your unique perspective as a report in the form of a beautiful web page.' We have completed two stages of work: 1) Collected recent AI hot events; 2) Collected detailed industry reports and analysis articles related to hotspots. Now we need to analyze startup opportunities in enterprise-level AI products based on this information.",
  task_description: "You need to serve as an AI industry analyst and complete the core work of writing analysis articles, outputting your unique perspective: 1) Read all hotspot_*.md files and ai_news_hotspots.md files to fully understand recent important developments in the AI field; 2) Particularly focus on information related to Agent products like Cursor, Manus, Genspark, Devin; 3) Analyze the current market landscape, technology trends, user needs and competitive situation of enterprise-level AI products; 4) Find unmet enterprise needs and market gaps; 5) Propose 3-5 promising directions for enterprise-level AI product startups, demonstrating their feasibility and differentiated advantages; 6) Organize your analysis and insights into a comprehensive report named 'ai_enterprise_opportunities.md'.",
  task_completion_standard: "Successfully generate a high-quality analysis report named 'ai_enterprise_opportunities.md' that must include: 1) Overview of current AI industry development, particularly Agent-class products; 2) Demand analysis and pain point identification for the enterprise-level AI market; 3) At least 3 specific directions for enterprise-level AI product startup opportunities, each direction must include: market positioning, core value proposition, technology feasibility analysis, competitive advantage analysis, potential risk assessment; 4) Specific implementation recommendations for each direction; 5) Report language must be professional, logic rigorous, perspectives must be original and forward-looking; 6) Report structure must be clear, convenient for subsequent conversion to web format. Ensure the report has both industry insights and practical value, capable of truly guiding startup decisions.",
  reference_files: [
    "./ai_news_hotspots.md",  # Hot events overview
    "./hotspot_ai_agents.md",  # AI agent product development trend analysis
    "./hotspot_llm_advances.md",  # Large language model latest technology breakthroughs
    "./hotspot_enterprise_ai.md"   # Enterprise-level AI application market dynamics
  ]
)

# Step 4: Use coder agent to convert analysis report to beautiful web page
call_agent(
  agent_name: "coder",
  agent_id: "ai-report-visualizer",
  task_background: "The user's original requirement is 'You can check the recent hot AI news through https://ai-bot.cn/daily-ai-news/, but the content inside is not detailed. Therefore, you can extract search keywords for various hot events from the news titles (noun extraction for events or new things should be accurate), then select a few events you think are most valuable, search for articles related to this event in WeChat official accounts, then take links to articles you think are most valuable, then access WeChat article links through browser to get complete Markdown content of articles, then search search engines for reports about this event from 36Kr, Huxiu and other media, then deeply understand the content of these articles, understand what's happening in the AI circle recently, finally answer an ultimate question \"In the context of Cursor, Manus, Genspark, Devin and other various Agent products competing fiercely, what opportunities are there for startup teams to do enterprise-level AI products?\", finally output your unique perspective as a report in the form of a beautiful web page.' We have completed the first three stages of work: 1) Collected AI hot events; 2) Conducted in-depth research on important hotspots; 3) Analyzed startup opportunities in enterprise-level AI products and generated an in-depth report. Now we need to convert the report into a visually attractive web page with clear information display.",
  task_description: "You are responsible for the final web page delivery stage, which is a crucial final link. You need to: 1) Read the 'ai_enterprise_opportunities.md' analysis report (other Markdown reference files are information collected during the process and can be consulted if necessary), understand its core structure and content; 2) Design and develop a modern, professional single-page website that prominently showcases the core viewpoints and startup opportunities in the report; 3) The website should include attractive data visualization elements, such as trend charts, market opportunity heat maps, etc.; 4) Ensure the website has good content hierarchy and navigation structure for easy reading of long analysis content; 5) Create separate display areas for each startup opportunity direction mentioned in the report, highlighting its core value proposition and differentiated advantages; 6) Add appropriate interactive elements to enhance user experience.",
  task_completion_standard: "Successfully develop a beautiful website with 'index.html' as the entry file. The website must meet the following standards: 1) Professional and modern design, fitting the style of technology industry analysis reports; 2) Complete and accurate display of all core content of the report; 3) Include at least 3 data visualization charts that intuitively display market trends or opportunities; 4) Clear structure with explicit navigation and content sections; 5) Good display on different screen sizes (responsive design); 6) Fast loading speed, not dependent on complex external resources; 7) Attractive visual design with professional color schemes; 8) Clear code structure with comprehensive comments; 9) Can run normally in any modern browser. Ensure the website is both beautiful and practical, effectively conveying the core value of the report. Do not fabricate content beyond the reference files - only content in reference files is authoritative and trustworthy information.",
  reference_files: [
    "./ai_enterprise_opportunities.md",  # Main analysis report
    "./ai_news_hotspots.md",  # Hot events overview
    "./hotspot_ai_agents.md",  # AI agent product development trend analysis
    "./hotspot_llm_advances.md",  # Large language model latest technology breakthroughs
    "./hotspot_enterprise_ai.md"   # Enterprise-level AI application market dynamics
  ]
)
      ]]>
    </example>
  </examples>
</tool>"""

        return hint

    async def execute(self, tool_context: ToolContext, params: CallAgentParams) -> ToolResult:
        """
        Execute agent call

        Args:
            tool_context: Tool context
            params: Parameter object containing agent name and task description

        Returns:
            ToolResult: Contains operation result
        """
        try:
            # Instantiate Agent based on agent_name
            from app.core.context.agent_context import AgentContext
            from app.delightful.agent import Agent
            new_agent_context = AgentContext()
            agent = Agent(params.agent_name, agent_id=params.agent_id, agent_context=new_agent_context)

            # Call agent's run method
            query_content = f"Background information (comprehensive lossless background information summary): {params.task_background}\nTask description (what you are responsible for, you only need to do this): {params.task_description}\nTask completion criteria (how to determine it's done): {params.task_completion_standard}"

            # Add reference file list and metadata
            if params.reference_files and len(params.reference_files) > 0:
                query_content += "\n\nReference file list:"
                for file_path in params.reference_files:
                    file_info = get_file_info(file_path)
                    query_content += f"\n- {file_info}"

            result = await agent.run(query_content)

            # Ensure result is string type
            if result is None:
                result = f"Agent {params.agent_name} executed successfully, but returned no result"
            elif not isinstance(result, str):
                result = str(result)

            return ToolResult(content=result)

        except Exception as e:
            logger.exception(f"Failed to call agent: {e!s}")
            return ToolResult(error=f"Failed to call agent: {e!s}")

    async def get_before_tool_call_friendly_content(self, tool_context: ToolContext, arguments: Dict[str, Any] = None) -> str:
        """
        Get friendly content before tool call
        """
        return ""
