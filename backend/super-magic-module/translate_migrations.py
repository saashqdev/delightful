#!/usr/bin/env python3
"""
Script to translate Chinese comments in migration files to English.
"""

import re
from pathlib import Path

# Translation dictionary for common database field terms
TRANSLATIONS = {
    # Common field names
    "用户": "user",
    "用户id": "user ID",
    "用户ID": "user ID",
    "用户组织编码": "user organization code",
    "聊天会话id": "chat conversation ID",
    "工作区名称": "workspace name",
    "是否归档": "whether archived",
    "创建者用户ID": "creator user ID",
    "更新者用户ID": "updater user ID",
    "当前话题ID": "current topic ID",
    "状态": "status",
    "正常": "normal",
    "不显示": "not displayed",
    "删除": "deleted",
    "发送者类型": "sender type",
    "发送者ID": "sender ID",
    "接收者ID": "receiver ID",
    "消息ID": "message ID",
    "消息类型": "message type",
    "任务ID": "task ID",
    "话题ID": "topic ID",
    "任务状态": "task status",
    "消息内容": "message content",
    "步骤信息": "step information",
    "工具调用信息": "tool call information",
    "事件类型": "event type",
    "发送时间戳": "send timestamp",
    "项目ID": "project ID",
    "项目名称": "project name",
    "项目描述": "project description",
    "项目类型": "project type",
    "是否置顶": "whether pinned",
    "是否公开": "whether public",
    "是否删除": "whether deleted",
    "创建时间": "creation time",
    "更新时间": "update time",
    "否": "no",
    "是": "yes",
    "文件ID": "file ID",
    "文件名": "file name",
    "文件名称": "file name",
    "文件路径": "file path",
    "文件大小": "file size",
    "文件类型": "file type",
    "文件扩展名": "file extension",
    "是否是文件夹": "whether is folder",
    "父级ID": "parent ID",
    "存储路径": "storage path",
    "存储类型": "storage type",
    "云端": "cloud",
    "本地": "local",
    "版本号": "version number",
    "是否当前版本": "whether current version",
    "提交信息": "commit message",
    "提交哈希": "commit hash",
    "提交时间": "commit time",
    "分支名称": "branch name",
    "标签": "tag",
    "标签名称": "tag name",
    "描述": "description",
    "备注": "note",
    "排序": "sort order",
    "排序值": "sort value",
    "显示顺序": "display order",
    "是否启用": "whether enabled",
    "启用": "enabled",
    "禁用": "disabled",
    "是否显示": "whether displayed",
    "显示": "display",
    "隐藏": "hide",
    "是否隐藏": "whether hidden",
    "权限": "permission",
    "权限级别": "permission level",
    "角色": "role",
    "角色ID": "role ID",
    "角色名称": "role name",
    "成员ID": "member ID",
    "成员类型": "member type",
    "邀请码": "invitation code",
    "邀请链接": "invitation link",
    "邀请人ID": "inviter ID",
    "被邀请人ID": "invitee ID",
    "邀请时间": "invitation time",
    "接受时间": "acceptance time",
    "过期时间": "expiration time",
    "是否过期": "whether expired",
    "密码": "password",
    "是否需要密码": "whether password required",
    "分享码": "share code",
    "分享链接": "share link",
    "分享类型": "share type",
    "分享状态": "share status",
    "访问次数": "access count",
    "最后访问时间": "last access time",
    "资源ID": "resource ID",
    "资源类型": "resource type",
    "内容": "content",
    "内容类型": "content type",
    "扩展信息": "extension information",
    "配置": "configuration",
    "配置信息": "configuration information",
    "参数": "parameter",
    "参数配置": "parameter configuration",
    "模型": "model",
    "模型名称": "model name",
    "模型配置": "model configuration",
    "提示词": "prompt",
    "提示词模板": "prompt template",
    "系统提示词": "system prompt",
    "令牌使用": "token usage",
    "输入令牌": "input tokens",
    "输出令牌": "output tokens",
    "总令牌": "total tokens",
    "成本": "cost",
    "总成本": "total cost",
    "货币": "currency",
    "记录时间": "record time",
    "执行时间": "execution time",
    "开始时间": "start time",
    "结束时间": "end time",
    "耗时": "duration",
    "错误信息": "error message",
    "错误码": "error code",
    "成功": "success",
    "失败": "failed",
    "进行中": "in progress",
    "已完成": "completed",
    "已取消": "cancelled",
    "队列名称": "queue name",
    "队列状态": "queue status",
    "优先级": "priority",
    "重试次数": "retry count",
    "最大重试次数": "max retry count",
    "定时任务": "scheduled task",
    "执行规则": "execution rule",
    "cron表达式": "cron expression",
    "下次执行时间": "next execution time",
    "是否激活": "whether active",
    "附件": "attachment",
    "附件ID": "attachment ID",
    "附件列表": "attachment list",
    "附件类型": "attachment type",
    "附件大小": "attachment size",
    "上传时间": "upload time",
    "下载次数": "download count",
    "沙箱": "sandbox",
    "沙箱ID": "sandbox ID",
    "沙箱状态": "sandbox status",
    "沙箱配置": "sandbox configuration",
    "环境变量": "environment variable",
    "镜像": "image",
    "镜像版本": "image version",
    "容器ID": "container ID",
    "端口": "port",
    "协议": "protocol",
    "主机": "host",
    "地址": "address",
    "URL": "URL",
    "链接": "link",
    "令牌": "token",
    "访问令牌": "access token",
    "刷新令牌": "refresh token",
    "过期": "expired",
    "有效": "valid",
    "无效": "invalid",
    "序号": "sequence number",
    "序列号": "serial number",
    "IM序列号": "IM sequence ID",
    "模式": "mode",
    "任务模式": "task mode",
    "编辑模式": "edit mode",
    "查看模式": "view mode",
    "菜单": "menu",
    "菜单配置": "menu configuration",
    "是否在菜单中显示": "whether displayed in menu",
    "版本": "version",
    "版本信息": "version information",
    "git提交哈希": "git commit hash",
    "当前项目ID": "current project ID",
    "默认": "default",
    "自定义": "custom",
    "类型": "type",
    "名称": "name",
    "值": "value",
    "键": "key",
    "数据": "data",
    "元数据": "metadata",
    "JSON数据": "JSON data",
    "额外数据": "extra data",
    "关联ID": "associated ID",
    "关联类型": "associated type",
    "引用": "reference",
    "来源": "source",
    "目标": "target",
    "操作": "operation",
    "操作类型": "operation type",
    "操作人": "operator",
    "操作时间": "operation time",
    "IP地址": "IP address",
    "用户代理": "user agent",
    "设备": "device",
    "设备类型": "device type",
    "浏览器": "browser",
    "平台": "platform",
    "位置": "location",
    "国家": "country",
    "城市": "city",
    "语言": "language",
    "时区": "timezone",
    "编码": "encoding",
    "字符集": "charset",
    "哈希": "hash",
    "校验和": "checksum",
    "签名": "signature",
    "加密": "encrypted",
    "解密": "decrypted",
    "公钥": "public key",
    "私钥": "private key",
    "证书": "certificate",
    "索引": "index",
    "唯一索引": "unique index",
    "外键": "foreign key",
    "主键": "primary key",
    "自增": "auto increment",
    "默认值": "default value",
    "可空": "nullable",
    "非空": "not null",
    "长度": "length",
    "最大长度": "max length",
    "最小长度": "min length",
    "格式": "format",
    "正则表达式": "regular expression",
    "验证": "validation",
    "规则": "rule",
    "约束": "constraint",
}

def translate_comment(chinese_text):
    """Translate Chinese text to English using the translation dictionary."""
    # Direct phrase translations for common patterns
    direct_phrases = {
        "消息序列ID，用于消息顺序追踪": "message sequence ID for message order tracking",
        "消息序列ID": "message sequence ID",
        "用于消息顺序追踪": "for message order tracking",
        "用于": "for",
        "，": ", ",
        "。": ". ",
    }
    
    # Try direct phrase match first
    for cn, en in direct_phrases.items():
        if cn in chinese_text:
            chinese_text = chinese_text.replace(cn, en)
    
    # Try direct match from main dictionary
    if chinese_text in TRANSLATIONS:
        return TRANSLATIONS[chinese_text]
    
    # Try to match parts (longest first to avoid partial matches)
    result = chinese_text
    for cn, en in sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True):
        if cn in result:
            result = result.replace(cn, en)
    
    return result

def process_migration_file(file_path):
    """Process a single migration file and translate Chinese comments."""
    content = file_path.read_text(encoding='utf-8')
    original_content = content
    
    # Find all comment() patterns with Chinese text
    def replace_comment(match):
        full_match = match.group(0)
        comment_text = match.group(1)
        
        # Check if contains Chinese
        if re.search(r'[\u4e00-\u9fff]', comment_text):
            translated = translate_comment(comment_text)
            return f"->comment('{translated}')"
        return full_match
    
    # Replace comment('...') patterns
    content = re.sub(r"->comment\('([^']+)'\)", replace_comment, content)
    
    # Replace comment("...") patterns
    def replace_comment_double(match):
        full_match = match.group(0)
        comment_text = match.group(1)
        
        if re.search(r'[\u4e00-\u9fff]', comment_text):
            translated = translate_comment(comment_text)
            return f'->comment("{translated}")'
        return full_match
    
    content = re.sub(r'->comment\("([^"]+)"\)', replace_comment_double, content)
    
    # Only write if content changed
    if content != original_content:
        file_path.write_text(content, encoding='utf-8')
        return True
    return False

def main():
    """Main entry point."""
    migrations_dir = Path(r'c:\Users\kubew\magic\backend\super-magic-module\migrations')
    
    if not migrations_dir.exists():
        print(f"Migrations directory not found: {migrations_dir}")
        return
    
    migration_files = list(migrations_dir.glob('*.php'))
    print(f"Found {len(migration_files)} migration files")
    
    translated_count = 0
    for file_path in migration_files:
        if process_migration_file(file_path):
            translated_count += 1
            print(f"Translated: {file_path.name}")
    
    print(f"\nTranslation complete!")
    print(f"Files processed: {len(migration_files)}")
    print(f"Files translated: {translated_count}")

if __name__ == "__main__":
    main()
