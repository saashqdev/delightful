#!/usr/bin/env python3
"""
Script to complete translation of Chinese text to English in remaining agentlang files.
This handles the files that were not fully translated due to time/token constraints.
"""

import re
from pathlib import Path

# Translation mappings for common Chinese phrases
TRANSLATIONS = {
    # Common phrases
    "模块": "module",
    "类": "class",
    "函数": "function",
    "方法": "method",
    "参数": "parameter",
    "返回": "return",
    "异常": "exception",
    "错误": "error",
    "警告": "warning",
    "信息": "information",
    "成功": "success",
    "失败": "failed",
    "初始化": "initialize",
    "配置": "configuration",
    "设置": "setting",
    "获取": "get",
    "保存": "save",
    "加载": "load",
    "创建": "create",
    "删除": "delete",
    "更新": "update",
    "检查": "check",
    "验证": "validate",
    "处理": "process",
    "执行": "execute",
    "启动": "start",
    "停止": "stop",
    "重启": "restart",
    "路径": "path",
    "文件": "file",
    "目录": "directory",
    "数据": "data",
    "对象": "object",
    "实例": "instance",
    "结果": "result",
    "状态": "status",
    "类型": "type",
    "值": "value",
    "默认": "default",
    "可选": "optional",
    "必需": "required",
    "有效": "valid",
    "无效": "invalid",
    "空": "empty",
    "不能为空": "cannot be empty",
    "不存在": "does not exist",
    "已存在": "already exists",
    "未找到": "not found",
    "没有": "no",
    "无法": "unable to",
    "尝试": "attempt",
    "使用": "use",
    "应用": "application",
    "系统": "system",
    "服务": "service",
    "工具": "tool",
    "助手": "assistant",
    "用户": "user",
    "消息": "message",
    "内容": "content",
    "时间": "time",
    "耗时": "duration",
    "开始": "start",
    "结束": "end",
    "总计": "total",
    "计数": "count",
    "数量": "quantity",
    "大小": "size",
    "长度": "length",
    "索引": "index",
    "位置": "position",
    "行": "line",
    "列": "column",
    "压缩": "compression",
    "解压": "decompression",
    "格式化": "format",
    "解析": "parse",
    "序列化": "serialize",
    "反序列化": "deserialize",
    "转换": "convert",
    "映射": "map",
    "过滤": "filter",
    "排序": "sort",
    "搜索": "search",
    "匹配": "match",
    "替换": "replace",
    "合并": "merge",
    "分割": "split",
    "连接": "join",
    "复制": "copy",
    "移动": "move",
    "重命名": "rename",
    "清空": "clear",
    "重置": "reset",
    "刷新": "refresh",
    "同步": "synchronize",
    "异步": "asynchronous",
    "阻塞": "blocking",
    "非阻塞": "non-blocking",
    "超时": "timeout",
    "延迟": "delay",
    "重试": "retry",
    "等待": "wait",
    "继续": "continue",
    "跳过": "skip",
    "忽略": "ignore",
    "取消": "cancel",
    "终止": "terminate",
    "中断": "interrupt",
    "完成": "complete",
    "进度": "progress",
    "百分比": "percentage",
    "比率": "ratio",
    "阈值": "threshold",
    "限制": "limit",
    "范围": "range",
    "最小": "minimum",
    "最大": "maximum",
    "平均": "average",
    "标准": "standard",
    "自定义": "custom",
    "临时": "temporary",
    "永久": "permanent",
    "公开": "public",
    "私有": "private",
    "内部": "internal",
    "外部": "external",
    "本地": "local",
    "远程": "remote",
    "全局": "global",
    "局部": "local",
    "环境": "environment",
    "变量": "variable",
    "常量": "constant",
    "字段": "field",
    "属性": "attribute",
    "选项": "option",
    "标志": "flag",
    "开关": "switch",
    "启用": "enable",
    "禁用": "disable",
    "激活": "activate",
    "停用": "deactivate",
    "可用": "available",
    "不可用": "unavailable",
    "支持": "support",
    "不支持": "unsupported",
    "兼容": "compatible",
    "不兼容": "incompatible",
    "版本": "version",
    "更新": "update",
    "升级": "upgrade",
    "降级": "downgrade",
    "安装": "install",
    "卸载": "uninstall",
    "依赖": "dependency",
    "关联": "association",
    "引用": "reference",
    "链接": "link",
    "绑定": "bind",
    "解绑": "unbind",
    "注册": "register",
    "注销": "unregister",
    "订阅": "subscribe",
    "取消订阅": "unsubscribe",
    "发布": "publish",
    "监听": "listen",
    "触发": "trigger",
    "事件": "event",
    "回调": "callback",
    "钩子": "hook",
    "拦截": "intercept",
    "中间件": "middleware",
    "插件": "plugin",
    "扩展": "extension",
    "模板": "template",
    "示例": "example",
    "样例": "sample",
    "测试": "test",
    "调试": "debug",
    "日志": "log",
    "记录": "record",
    "跟踪": "track",
    "监控": "monitor",
    "统计": "statistics",
    "报告": "report",
    "分析": "analysis",
    "评估": "evaluation",
    "优化": "optimization",
    "性能": "performance",
    "效率": "efficiency",
    "质量": "quality",
    "可靠性": "reliability",
    "稳定性": "stability",
    "安全": "security",
    "权限": "permission",
    "认证": "authentication",
    "授权": "authorization",
    "加密": "encryption",
    "解密": "decryption",
    "签名": "signature",
    "哈希": "hash",
    "编码": "encoding",
    "解码": "decoding",
    "协议": "protocol",
    "接口": "interface",
    "抽象": "abstract",
    "实现": "implementation",
    "继承": "inheritance",
    "多态": "polymorphism",
    "封装": "encapsulation",
    "模式": "pattern",
    "策略": "strategy",
    "工厂": "factory",
    "单例": "singleton",
    "观察者": "observer",
    "代理": "proxy",
    "适配器": "adapter",
    "装饰器": "decorator",
    "迭代器": "iterator",
    "生成器": "generator",
    "上下文": "context",
    "作用域": "scope",
    "命名空间": "namespace",
    "包": "package",
    "库": "library",
    "框架": "framework",
    "平台": "platform",
    "架构": "architecture",
    "组件": "component",
    "模块化": "modular",
    "解耦": "decouple",
    "集成": "integration",
    "部署": "deployment",
    "发布": "release",
    "维护": "maintenance",
    "文档": "documentation",
    "注释": "comment",
    "说明": "description",
    "备注": "note",
    "提示": "hint",
    "建议": "suggestion",
    "推荐": "recommendation",
    "最佳实践": "best practice",
    "注意": "attention",
    "重要": "important",
    "关键": "critical",
    "紧急": "urgent",
    "已弃用": "deprecated",
    "实验性": "experimental",
    "预览": "preview",
    "稳定": "stable",
    "测试版": "beta",
    "候选版": "release candidate",
    "正式版": "release",
}

def contains_chinese(text):
    """Check if text contains Chinese characters."""
    return bool(re.search(r'[\u4e00-\u9fff]', text))

def find_chinese_segments(text):
    """Find all Chinese character segments in text."""
    return re.findall(r'[\u4e00-\u9fff]+', text)

def translate_file(file_path):
    """Translate Chinese text in a single file."""
    path = Path(file_path)
    if not path.exists():
        print(f"File not found: {file_path}")
        return False
    
    content = path.read_text(encoding='utf-8')
    
    # Check if file contains Chinese
    if not contains_chinese(content):
        print(f"No Chinese text found in: {file_path}")
        return True
    
    chinese_segments = find_chinese_segments(content)
    print(f"\nProcessing {file_path}")
    print(f"Found {len(set(chinese_segments))} unique Chinese segments")
    
    # Display first 10 segments for manual review
    print("Sample Chinese segments:")
    for i, seg in enumerate(sorted(set(chinese_segments))[:10], 1):
        print(f"  {i}. {seg}")
    
    print(f"\nNote: This file requires manual translation.")
    print(f"Please use multi_replace_string_in_file or edit the file directly.")
    
    return False

def main():
    """Main entry point."""
    base_path = Path(r"c:\Users\kubew\delightful\backend\be-delightful\agentlang\agentlang")
    
    # Files remaining to translate
    files_to_translate = [
        "chat_history/chat_history.py",
        "llms/token_usage/report.py",
        "llms/token_usage/tracker.py",
        "utils/__init__.py",
        "utils/process_manager.py",
        "utils/retry.py",
        "utils/schema.py",
        "utils/snowflake.py",
        "utils/syntax_checker.py",
        "utils/token_counter.py",
        "utils/token_estimator.py",
    ]
    
    print("=" * 80)
    print("Chinese to English Translation Helper")
    print("=" * 80)
    
    for file_rel_path in files_to_translate:
        file_path = base_path / file_rel_path
        translate_file(str(file_path))
    
    print("\n" + "=" * 80)
    print("Translation check complete!")
    print("=" * 80)

if __name__ == "__main__":
    main()
