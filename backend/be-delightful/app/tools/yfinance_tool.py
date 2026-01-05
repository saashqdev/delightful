"""YFinance tool module

Provides functions to fetch financial market data from Yahoo Finance.
"""

import asyncio
import json
from datetime import datetime
from typing import Any, Dict, Optional

import yfinance as yf
from pydantic import Field, field_validator

from agentlang.context.tool_context import ToolContext
from agentlang.logger import get_logger
from agentlang.tools.tool_result import ToolResult
from app.core.entity.tool.tool_result import YFinanceToolResult
from app.tools.core import BaseTool, BaseToolParams, tool

logger = get_logger(__name__)

# Available query types
QUERY_TYPES = ["history", "info", "financials", "actions", "dividends", "institutional_holders",
              "major_holders", "balance_sheet", "cashflow", "earnings", "sustainability",
              "recommendations", "calendar", "news", "market_status"]

# Time period mapping
PERIOD_MAPPING = {
    "1d": "1 day",
    "5d": "5 days",
    "1mo": "1 month",
    "3mo": "3 months",
    "6mo": "6 months",
    "1y": "1 year",
    "2y": "2 years",
    "5y": "5 years",
    "10y": "10 years",
    "ytd": "Year to date",
    "max": "Maximum range"
}

# Valid intervals
VALID_INTERVALS = ["1m", "2m", "5m", "15m", "30m", "60m", "90m", "1h", "1d", "5d", "1wk", "1mo", "3mo"]


class YFinanceParams(BaseToolParams):
    """YFinance tool parameters"""

    ticker: str = Field(
        ...,
        description="Stock symbol or list. Examples: AAPL, MSFT, GOOG, BABA, 9988.HK, 600519.SS"
    )

    query_type: str = Field(
        ...,
        description=f"Query type, supported options: {', '.join(QUERY_TYPES)}"
    )

    period: str = Field(
        "1mo",
        description="Time period, only for history query. Options: 1d, 5d, 1mo, 3mo, 6mo, 1y, 2y, 5y, 10y, ytd, max"
    )

    interval: str = Field(
        "1d",
        description="Price interval, only for history query. Options: 1m, 2m, 5m, 15m, 30m, 60m, 90m, 1h, 1d, 5d, 1wk, 1mo, 3mo"
    )

    limit: int = Field(
        10,
        description="Maximum number of results to return"
    )

    @field_validator("query_type")
    def validate_query_type(cls, v):
        """Validate query type"""
        if v not in QUERY_TYPES:
            raise ValueError(f"Invalid query type: {v}. Valid options: {', '.join(QUERY_TYPES)}")
        return v

    @field_validator("period")
    def validate_period(cls, v):
        """Validate time period"""
        if v not in PERIOD_MAPPING:
            raise ValueError(f"Invalid time period: {v}. Valid options: {', '.join(PERIOD_MAPPING.keys())}")
        return v

    @field_validator("interval")
    def validate_interval(cls, v):
        """Validate time interval"""
        if v not in VALID_INTERVALS:
            raise ValueError(f"Invalid interval: {v}. Valid options: {', '.join(VALID_INTERVALS)}")
        return v

    @classmethod
    def get_custom_error_message(cls, field_name: str, error_type: str) -> Optional[str]:
        """Get custom parameter error information"""
        if field_name == "ticker" and error_type == "missing":
            return "Please provide a stock symbol, e.g., AAPL, MSFT, or 600519.SS"

        if field_name == "query_type" and error_type == "missing":
            return f"Please provide query type. Valid options: {', '.join(QUERY_TYPES)}"

        return None


@tool()
class YFinance(BaseTool[YFinanceParams]):
    """Yahoo Finance data tool"""

    # Set params class
    params_class = YFinanceParams

    # Tool metadata
    name = "yfinance"
    description = """Yahoo Finance data tool for fetching stock and market data.
Supports historical prices, company fundamentals, financial data, dividends, and more.
Data source: Yahoo Finance via yfinance library.

Use cases:
- Fetch historical prices and trends
- Get company fundamentals and financial health
- Get dividend data and history
- Retrieve financial statements (balance sheet, cash flow, etc.)
- Get institutional/major holders information
- Get market status and popular tickers

Supported query types:
- history: Historical price data
- info: Company fundamentals and overview
- financials: Financial statements summary
- actions: Corporate actions (dividends, splits, etc.)
- dividends: Dividend history
- institutional_holders: Institutional holders
- major_holders: Major holders
- balance_sheet: Balance sheet
- cashflow: Cash flow statement
- earnings: Earnings data
- sustainability: Sustainability ratings
- recommendations: Analyst recommendations
- calendar: Earnings/calendar events
- news: Related news
- market_status: Market status

Notes:
- Provide accurate ticker symbols; US stocks use plain ticker (e.g., AAPL), HK stocks append .HK (e.g., 9988.HK), A-shares append .SS or .SZ (e.g., 600519.SS)
- History queries support different time periods and intervals
- Some query types may not be available for certain tickers
- Data may be delayed and is not suitable for real-time trading decisions
"""

    async def execute(
        self,
        tool_context: ToolContext,
        params: YFinanceParams
    ) -> ToolResult:
        """
        Execute YFinance query and return formatted result.

        Args:
            tool_context: tool context
            params: Query parameters object

        Returns:
            YFinanceToolResult: Tool result containing query output
        """
        try:
            # getparameters
            ticker = params.ticker
            query_type = params.query_type
            period = params.period
            interval = params.interval
            limit = params.limit

            # Log query request
            logger.info(f"Executing YFinance query: ticker={ticker}, type={query_type}, period={period}, interval={interval}")

            # Create result object
            result = YFinanceToolResult(content="")
            result.set_ticker(ticker)
            result.set_query_type(query_type)

            if query_type == "history":
                result.set_time_period(period)

            # Execute query
            data = await self._perform_query(ticker, query_type, period, interval, limit)

            # Process query result
            if not data:
                return YFinanceToolResult(
                    error=f"No {query_type} data found for {ticker}",
                    ticker=ticker,
                    query_type=query_type
                )

            # Format output
            formatted_result = self._format_result(ticker, query_type, data, period)
            result.content = formatted_result

            return result

        except Exception as e:
            logger.exception(f"YFinance query failed: {e!s}")
            return YFinanceToolResult(error=f"Financial data query failed: {e!s}")

    async def _perform_query(
        self,
        ticker: str,
        query_type: str,
        period: str,
        interval: str,
        limit: int
    ) -> Any:
        """Execute actual YFinance query

        Args:
            ticker: stock symbol
            query_type: Query type
            period: time period
            interval: Time interval
            limit: Limit result count

        Returns:
            Any: Query result data
        """
        # Create async task
        loop = asyncio.get_event_loop()

        try:
            # Use run_in_executor to turn sync YFinance action into async
            result = await loop.run_in_executor(
                None,
                lambda: self._sync_query(ticker, query_type, period, interval, limit)
            )
            return result
        except Exception as e:
            logger.error(f"YFinance query failed: {e!s}")
            return None

    def _sync_query(
        self,
        ticker: str,
        query_type: str,
        period: str,
        interval: str,
        limit: int
    ) -> Any:
        """Synchronous YFinance query

        Args:
            ticker: stock symbol
            query_type: Query type
            period: time period
            interval: Time interval
            limit: Limit result count

        Returns:
            Any: Query result data
        """
        # Get ticker information
        ticker_obj = yf.Ticker(ticker)

        # Execute different actions by query type
        if query_type == "history":
            # Get historical price data
            df = ticker_obj.history(period=period, interval=interval)
            # Convert to list of dicts and limit
            records = df.tail(limit).reset_index().to_dict('records')
            # Format date
            for record in records:
                if 'Date' in record and isinstance(record['Date'], datetime):
                    record['Date'] = record['Date'].strftime('%Y-%m-%d %H:%M:%S')
            return records

        elif query_type == "info":
            # Get company info
            return ticker_obj.info

        elif query_type == "financials":
            # Get financial statements summary
            financials = ticker_obj.financials
            if financials is not None and not financials.empty:
                return financials.to_dict()
            return None

        elif query_type == "actions":
            # Get corporate actions (dividends, splits)
            actions = ticker_obj.actions
            if actions is not None and not actions.empty:
                records = actions.tail(limit).reset_index().to_dict('records')
                for record in records:
                    if 'Date' in record and isinstance(record['Date'], datetime):
                        record['Date'] = record['Date'].strftime('%Y-%m-%d')
                return records
            return None

        elif query_type == "dividends":
            # Get dividend history
            dividends = ticker_obj.dividends
            if dividends is not None and not dividends.empty:
                records = dividends.tail(limit).reset_index().to_dict('records')
                for record in records:
                    if 'Date' in record and isinstance(record['Date'], datetime):
                        record['Date'] = record['Date'].strftime('%Y-%m-%d')
                return records
            return None

        elif query_type == "institutional_holders":
            # Get institutional holders
            holders = ticker_obj.institutional_holders
            if holders is not None and not holders.empty:
                return holders.head(limit).to_dict('records')
            return None

        elif query_type == "major_holders":
            # Get major holders
            holders = ticker_obj.major_holders
            if holders is not None and not holders.empty:
                return holders.to_dict('records')
            return None

        elif query_type == "balance_sheet":
            # Get balance sheet
            balance_sheet = ticker_obj.balance_sheet
            if balance_sheet is not None and not balance_sheet.empty:
                return balance_sheet.to_dict()
            return None

        elif query_type == "cashflow":
            # Get cash flow statement
            cashflow = ticker_obj.cashflow
            if cashflow is not None and not cashflow.empty:
                return cashflow.to_dict()
            return None

        elif query_type == "earnings":
            # Get earnings data
            earnings = ticker_obj.earnings
            if earnings is not None and not earnings.empty:
                return earnings.to_dict()
            return None

        elif query_type == "sustainability":
            # Get sustainability ratings
            sustainability = ticker_obj.sustainability
            if sustainability is not None and not sustainability.empty:
                return sustainability.to_dict()
            return None

        elif query_type == "recommendations":
            # Get analyst recommendations
            recommendations = ticker_obj.recommendations
            if recommendations is not None and not recommendations.empty:
                records = recommendations.tail(limit).reset_index().to_dict('records')
                for record in records:
                    if 'Date' in record and isinstance(record['Date'], datetime):
                        record['Date'] = record['Date'].strftime('%Y-%m-%d')
                return records
            return None

        elif query_type == "calendar":
            # Get earnings/calendar
            calendar = ticker_obj.calendar
            if calendar is not None and not calendar.empty:
                return calendar.to_dict()
            return None

        elif query_type == "news":
            # Get related news
            news = ticker_obj.news
            return news[:limit] if news else None

        elif query_type == "market_status":
            # Get market status
            # yfinance has no direct market status API; approximate using major indices
            indices = ['^DJI', '^GSPC', '^IXIC', '^HSI', '000001.SS']  # Dow, S&P 500, Nasdaq, Hang Seng, SSE
            market_data = {}

            for index in indices:
                try:
                    index_ticker = yf.Ticker(index)
                    last_quote = index_ticker.history(period='1d')
                    if not last_quote.empty:
                        last_row = last_quote.iloc[-1]
                        change = last_row['Close'] - last_row['Open']
                        change_percent = (change / last_row['Open']) * 100

                        market_data[index] = {
                            'name': self._get_index_name(index),
                            'price': round(last_row['Close'], 2),
                            'change': round(change, 2),
                            'change_percent': round(change_percent, 2),
                            'volume': last_row['Volume']
                        }
                except Exception as e:
                    logger.error(f"Failed to fetch index {index} data: {e!s}")

            return market_data

        # Default return None
        return None

    def _format_result(self, ticker: str, query_type: str, data: Any, period: str = None) -> str:
        """Format query result into friendly text

        Args:
            ticker: stock symbol
            query_type: Query type
            data: Query result data
            period: time period

        Returns:
            str: Formatted result text
        """
        if not data:
            return f"No {query_type} data found for {ticker}"

        # Format differently per query type
        if query_type == "history":
            # Format historical price data
            period_text = PERIOD_MAPPING.get(period, period)
            result = {
                "message": f"Historical price data for {ticker} over past {period_text}",
                "data": data
            }
            return json.dumps(result, ensure_ascii=False)

        elif query_type == "info":
            # Format company info
            # Select key fields
            important_fields = [
                'shortName', 'longName', 'sector', 'industry', 'website',
                'market', 'marketCap', 'currency', 'exchange',
                'previousClose', 'open', 'dayLow', 'dayHigh', 'regularMarketPrice',
                'fiftyTwoWeekLow', 'fiftyTwoWeekHigh', 'volume', 'averageVolume',
                'trailingPE', 'forwardPE', 'dividendRate', 'dividendYield',
                'beta', 'bookValue', 'priceToBook', 'earningsGrowth',
                'revenueGrowth', 'totalRevenue', 'grossMargins', 'profitMargins'
            ]

            # Filter data to key fields
            filtered_data = {k: v for k, v in data.items() if k in important_fields and v is not None}

            result = {
                "message": f"Company information for {ticker}",
                "data": filtered_data
            }
            return json.dumps(result, ensure_ascii=False)

        elif query_type in ["financials", "balance_sheet", "cashflow", "earnings", "sustainability", "calendar"]:
            # DataFrame-derived nested dicts; need special handling
            result = {
                "message": f"{query_type} data for {ticker}",
                "data": self._process_dataframe_dict(data)
            }
            return json.dumps(result, ensure_ascii=False)

        elif query_type == "news":
            # Format news data
            formatted_news = []
            for news_item in data:
                formatted_news.append({
                    "title": news_item.get("title", ""),
                    "publisher": news_item.get("publisher", ""),
                    "link": news_item.get("link", ""),
                    "publish_time": news_item.get("providerPublishTime", ""),
                    "type": news_item.get("type", ""),
                    "relatedTickers": news_item.get("relatedTickers", [])
                })

            result = {
                "message": f"Related news for {ticker}",
                "data": formatted_news
            }
            return json.dumps(result, ensure_ascii=False)

        elif query_type == "market_status":
            # Format market status
            result = {
                "message": "Current major market indices status",
                "data": data
            }
            return json.dumps(result, ensure_ascii=False)

        else:
            # Other types: return JSON string
            result = {
                "message": f"{query_type} data for {ticker}",
                "data": data
            }
            return json.dumps(result, ensure_ascii=False)

    def _process_dataframe_dict(self, data: Dict) -> Dict:
        """Process nested dict converted from DataFrame for JSON serialization

        Args:
            data: Dictionary converted from DataFrame

        Returns:
            Dict: Processed dictionary
        """
        result = {}

        for column, values in data.items():
            column_data = {}

            for date, value in values.items():
                # Convert date objects to string
                if isinstance(date, datetime):
                    date_str = date.strftime('%Y-%m-%d')
                else:
                    date_str = str(date)

                # Ensure value is serializable
                if isinstance(value, (int, float, str, bool)) or value is None:
                    column_data[date_str] = value
                elif hasattr(value, 'item'):  # numpy value
                    column_data[date_str] = value.item()
                else:
                    column_data[date_str] = str(value)

            result[str(column)] = column_data

        return result

    def _get_index_name(self, index_symbol: str) -> str:
        """Get human-friendly index name

        Args:
            index_symbol: Index code

        Returns:
            str: Index name
        """
        index_names = {
            '^DJI': 'Dow Jones Industrial Average',
            '^GSPC': 'S&P 500',
            '^IXIC': 'NASDAQ Composite',
            '^HSI': 'Hang Seng Index',
            '000001.SS': 'SSE Composite Index'
        }

        return index_names.get(index_symbol, index_symbol)
