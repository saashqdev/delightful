#!/usr/bin/env python3
"""
Translate Chinese string literals in PHP files to English.
Handles error messages, validation messages, log output, etc.
"""

import re
from pathlib import Path
from typing import Dict, Set

# Comprehensive translation dictionary for common phrases
TRANSLATIONS = {
    # Code comments (English+Chinese mixed)
    '遍历': 'Traverse',
    '文件夹下所有文件': 'all files in folder',
    '助理执行事件': 'Assistant execution event',
    '接口到实现类映射': 'Interface to implementation class mapping',
    '相关服务依赖注入': 'related service dependency injection',
    '依赖注入': 'dependency injection',
    '管理': 'management',
    '如果需要': 'If needed',
    '手动': 'manually',
    '翻译行': 'translation lines',
    
    # Common terms
    '成功': 'Success',
    '失败': 'Failed',
    '错误': 'Error',
    '警告': 'Warning',
    '提示': 'Tip',
    '信息': 'Information',
    '确认': 'Confirm',
    '取消': 'Cancel',
    '删除': 'Delete',
    '添加': 'Add',
    '修改': 'Modify',
    '更新': 'Update',
    '保存': 'Save',
    '提交': 'Submit',
    '查询': 'Query',
    '搜索': 'Search',
    '导出': 'Export',
    '导入': 'Import',
    '上传': 'Upload',
    '下载': 'Download',
    '复制': 'Copy',
    '移动': 'Move',
    '重命名': 'Rename',
    '启用': 'Enable',
    '禁用': 'Disable',
    '发布': 'Publish',
    '撤销': 'Revoke',
    '审核': 'Review',
    '通过': 'Approved',
    '拒绝': 'Rejected',
    
    # Validation messages
    '参数错误': 'Invalid parameter',
    '参数不能为空': 'Parameter cannot be empty',
    '参数格式错误': 'Invalid parameter format',
    '参数类型错误': 'Invalid parameter type',
    '必填参数': 'Required parameter',
    '参数长度超出限制': 'Parameter length exceeds limit',
    '参数值超出范围': 'Parameter value out of range',
    
    # Error messages
    '未找到': 'Not found',
    '不存在': 'Does not exist',
    '已存在': 'Already exists',
    '无权限': 'No permission',
    '权限不足': 'Insufficient permission',
    '未授权': 'Unauthorized',
    '认证失败': 'Authentication failed',
    '登录失败': 'Login failed',
    '登录过期': 'Login expired',
    '会话过期': 'Session expired',
    '令牌无效': 'Invalid token',
    '令牌过期': 'Token expired',
    '签名错误': 'Invalid signature',
    '验证失败': 'Validation failed',
    '操作失败': 'Operation failed',
    '创建失败': 'Creation failed',
    '更新失败': 'Update failed',
    '删除失败': 'Deletion failed',
    '保存失败': 'Save failed',
    '查询失败': 'Query failed',
    '上传失败': 'Upload failed',
    '下载失败': 'Download failed',
    '导入失败': 'Import failed',
    '导出失败': 'Export failed',
    '发送失败': 'Send failed',
    '接收失败': 'Receive failed',
    '解析失败': 'Parse failed',
    '转换失败': 'Conversion failed',
    '处理失败': 'Processing failed',
    '执行失败': 'Execution failed',
    '连接失败': 'Connection failed',
    '超时': 'Timeout',
    '请求超时': 'Request timeout',
    '系统错误': 'System error',
    '网络错误': 'Network error',
    '数据库错误': 'Database error',
    '文件不存在': 'File does not exist',
    '文件已存在': 'File already exists',
    '文件格式错误': 'Invalid file format',
    '文件大小超出限制': 'File size exceeds limit',
    '文件上传失败': 'File upload failed',
    
    # Success messages
    '操作成功': 'Operation successful',
    '创建成功': 'Created successfully',
    '更新成功': 'Updated successfully',
    '删除成功': 'Deleted successfully',
    '保存成功': 'Saved successfully',
    '提交成功': 'Submitted successfully',
    '上传成功': 'Uploaded successfully',
    '下载成功': 'Downloaded successfully',
    '导入成功': 'Imported successfully',
    '导出成功': 'Exported successfully',
    '发送成功': 'Sent successfully',
    '发布成功': 'Published successfully',
    '复制成功': 'Copied successfully',
    '移动成功': 'Moved successfully',
    '重命名成功': 'Renamed successfully',
    
    # User/Account related
    '用户': 'User',
    '用户名': 'Username',
    '密码': 'Password',
    '邮箱': 'Email',
    '手机号': 'Phone number',
    '昵称': 'Nickname',
    '头像': 'Avatar',
    '账号': 'Account',
    '账户': 'Account',
    '角色': 'Role',
    '权限': 'Permission',
    '组织': 'Organization',
    '部门': 'Department',
    '团队': 'Team',
    '成员': 'Member',
    '管理员': 'Administrator',
    
    # Data related
    '数据': 'Data',
    '记录': 'Record',
    '列表': 'List',
    '详情': 'Details',
    '统计': 'Statistics',
    '总数': 'Total',
    '数量': 'Quantity',
    '页码': 'Page number',
    '每页条数': 'Items per page',
    '排序': 'Sort',
    '筛选': 'Filter',
    
    # Status related
    '状态': 'Status',
    '待审核': 'Pending review',
    '审核中': 'Under review',
    '已审核': 'Reviewed',
    '已通过': 'Approved',
    '已拒绝': 'Rejected',
    '草稿': 'Draft',
    '已发布': 'Published',
    '已下线': 'Offline',
    '进行中': 'In progress',
    '已完成': 'Completed',
    '已取消': 'Canceled',
    '已过期': 'Expired',
    '正常': 'Normal',
    '异常': 'Abnormal',
    '启用中': 'Enabled',
    '已禁用': 'Disabled',
    
    # Time related
    '时间': 'Time',
    '日期': 'Date',
    '开始时间': 'Start time',
    '结束时间': 'End time',
    '创建时间': 'Created at',
    '更新时间': 'Updated at',
    '删除时间': 'Deleted at',
    '过期时间': 'Expired at',
    
    # File related
    '文件': 'File',
    '文件夹': 'Folder',
    '目录': 'Directory',
    '文件名': 'Filename',
    '文件类型': 'File type',
    '文件大小': 'File size',
    '文件路径': 'File path',
    
    # Action related
    '请': 'Please',
    '请输入': 'Please enter',
    '请选择': 'Please select',
    '请上传': 'Please upload',
    '请确认': 'Please confirm',
    '是否': 'Whether to',
    '确定要': 'Are you sure to',
    
    # Common phrases
    '不能为空': 'Cannot be empty',
    '格式不正确': 'Incorrect format',
    '长度不符合要求': 'Length does not meet requirements',
    '超出限制': 'Exceeds limit',
    '不匹配': 'Does not match',
    '已被使用': 'Already in use',
    '重复': 'Duplicate',
    '无效的': 'Invalid',
    '非法的': 'Illegal',
    '暂无': 'None',
    '暂无数据': 'No data',
    '加载中': 'Loading',
    '处理中': 'Processing',
    
    # Workspace/Project related
    '工作空间': 'Workspace',
    '项目': 'Project',
    '任务': 'Task',
    '主题': 'Topic',
    '话题': 'Topic',
    '文档': 'Document',
    '笔记': 'Note',
    '标签': 'Tag',
    '分类': 'Category',
    '描述': 'Description',
    '备注': 'Remark',
    '内容': 'Content',
    '标题': 'Title',
    '名称': 'Name',
    '类型': 'Type',
    '配置': 'Configuration',
    '设置': 'Settings',
    
    # Agent/AI related
    '智能体': 'Agent',
    '对话': 'Conversation',
    '聊天': 'Chat',
    '消息': 'Message',
    '回复': 'Reply',
    '会话': 'Session',
    '历史': 'History',
    '记录': 'Record',
    
    # Invitation/Share related
    '邀请': 'Invitation',
    '邀请码': 'Invitation code',
    '分享': 'Share',
    '分享链接': 'Share link',
    '链接': 'Link',
    '协作': 'Collaboration',
    '协作者': 'Collaborator',
    
    # Specific error messages
    '该': 'This',
    '此': 'This',
    '当前': 'Current',
    '所选': 'Selected',
    '已': 'Already',
    '尚未': 'Not yet',
    '无法': 'Unable to',
    '不支持': 'Not supported',
    '仅支持': 'Only support',
    '必须': 'Must',
    '应该': 'Should',
    '可以': 'Can',
    '或': 'Or',
    '和': 'And',
    '的': '',  # possessive particle, often removed in English
    '了': '',  # aspect particle, often removed
    '吗': '',  # question particle, often removed
}

def find_chinese_in_string_literals(file_path: Path) -> list:
    """Find all Chinese characters in string literals."""
    try:
        content = file_path.read_text(encoding='utf-8')
    except Exception as e:
        print(f"Error reading {file_path}: {e}")
        return []
    
    matches = []
    
    # Pattern for single-quoted strings
    single_pattern = r"'([^'\\]*(?:\\.[^'\\]*)*)'"
    # Pattern for double-quoted strings
    double_pattern = r'"([^"\\]*(?:\\.[^"\\]*)*)"'
    
    for pattern in [single_pattern, double_pattern]:
        for match in re.finditer(pattern, content):
            string_content = match.group(1)
            if re.search(r'[\u4e00-\u9fff]', string_content):
                matches.append({
                    'original': match.group(0),
                    'content': string_content,
                    'quote': match.group(0)[0]
                })
    
    return matches

def translate_chinese(text: str) -> str:
    """Translate Chinese text to English using dictionary."""
    # Sort by length (longest first) to match longer phrases first
    sorted_translations = sorted(TRANSLATIONS.items(), key=lambda x: len(x[0]), reverse=True)
    
    result = text
    for chinese, english in sorted_translations:
        result = result.replace(chinese, english)
    
    return result

def process_file(file_path: Path) -> bool:
    """Process a single PHP file."""
    try:
        content = file_path.read_text(encoding='utf-8')
        original_content = content
        
        # Find all string literals with Chinese
        matches = find_chinese_in_string_literals(file_path)
        
        if not matches:
            return False
        
        # Process each match
        replacements = []
        for match in matches:
            original_str = match['original']
            chinese_content = match['content']
            quote = match['quote']
            
            # Translate the content
            translated = translate_chinese(chinese_content)
            
            # Only replace if translation changed something
            if translated != chinese_content:
                new_str = f"{quote}{translated}{quote}"
                replacements.append((original_str, new_str))
        
        # Apply replacements
        for old, new in replacements:
            # Be careful with replacements - only replace exact matches
            content = content.replace(old, new, 1)
        
        if content != original_content:
            file_path.write_text(content, encoding='utf-8')
            return True
            
        return False
        
    except Exception as e:
        print(f"Error processing {file_path}: {e}")
        return False

def main():
    """Main translation process."""
    base_path = Path(__file__).parent
    
    # Find all PHP files (excluding zh_CN language files)
    all_php_files = list(base_path.rglob('*.php'))
    php_files = [f for f in all_php_files if 'zh_CN' not in str(f)]
    
    print(f"Found {len(php_files)} PHP files to process")
    print("Starting string literal translation...")
    print("=" * 60)
    
    modified_count = 0
    
    for i, file_path in enumerate(php_files, 1):
        if i % 50 == 0:
            print(f"Progress: {i}/{len(php_files)} files processed...")
        
        if process_file(file_path):
            modified_count += 1
    
    print("=" * 60)
    print(f"Translation complete!")
    print(f"Modified {modified_count} files")
    
    # Count remaining Chinese
    total_chinese = 0
    for file_path in php_files:
        try:
            content = file_path.read_text(encoding='utf-8')
            chinese_count = len(re.findall(r'[\u4e00-\u9fff]+', content))
            total_chinese += chinese_count
        except:
            pass
    
    print(f"Remaining Chinese strings: {total_chinese}")

if __name__ == '__main__':
    main()
