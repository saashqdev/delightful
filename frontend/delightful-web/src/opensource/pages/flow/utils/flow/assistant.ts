/**
 * Flow assistant utility functions
 * Provides functionality for handling flow assistant features
 */

/**
 * Extract content from flow assistant messages
 * @param message Assistant message
 * @returns Extracted content
 */
export const extractContent = (message: string): string => {
  try {
    if (!message) return '';
    // Handle quotes and escape characters
    return message
      .replace(/\\"/g, '"')  // Replace escaped double quotes
      .replace(/\\n/g, '\n') // Replace escaped newlines
      .replace(/\\t/g, '\t') // Replace escaped tabs
      .replace(/\\\\/g, '\\'); // Replace escaped backslashes
  } catch (error) {
    console.error('Error extracting content:', error);
    return message || '';
  }
};

/**
 * Parse command
 * @param command Command string
 * @returns Parsed command object
 */
export const parseCommand = (command: string): any => {
  try {
    if (!command) return null;
    return JSON.parse(command);
  } catch (error) {
    console.error('Failed to parse command:', error);
    return null;
  }
};

/**
 * Process stream data
 * @param data Stream data
 * @returns Processed data
 */
export const processStreamData = (data: string): { content: string, commands: any[] } => {
  try {
    const lines = data.split('\n').filter(line => line.trim() !== '');
    let content = '';
    const commands: any[] = [];

    for (const line of lines) {
      if (line.startsWith('data:')) {
        const jsonStr = line.substring(5).trim();
        if (jsonStr === '[DONE]') continue;
        
        try {
          const parsedData = JSON.parse(jsonStr);
          if (parsedData.content) {
            content += parsedData.content;
          }
          if (parsedData.command) {
            commands.push(parseCommand(parsedData.command));
          }
        } catch (e) {
          console.error('Failed to parse stream data line:', e);
        }
      }
    }

    return { content, commands };
  } catch (error) {
    console.error('Failed to process stream data:', error);
    return { content: '', commands: [] };
  }
};

/**
 * Deduplicate commands
 * @param commands Command array
 * @returns Deduplicated command array
 */
export const deduplicateCommands = (commands: any[]): any[] => {
  if (!commands || commands.length === 0) return [];
  
  const seen = new Set();
  return commands.filter(cmd => {
    if (!cmd) return false;
    
    // Create unique identifier, e.g.: operation type + node ID
    const identifier = `${cmd.type || ''}_${cmd.nodeId || ''}_${cmd.nodeType || ''}`;
    if (seen.has(identifier)) return false;
    
    seen.add(identifier);
    return true;
  });
};

/**
 * Get progressive content for typing effect
 * @param fullContent Full content
 * @param displayedLength Length already displayed
 * @param charsPerFrame Number of characters to add per frame
 * @returns Content to display
 */
export const getTypingEffect = (
  fullContent: string, 
  displayedLength: number, 
  charsPerFrame: number = 3
): { 
  displayContent: string, 
  isComplete: boolean, 
  newLength: number 
} => {
  if (!fullContent) {
    return { displayContent: '', isComplete: true, newLength: 0 };
  }

  const newLength = Math.min(displayedLength + charsPerFrame, fullContent.length);
  const displayContent = fullContent.substring(0, newLength);
  const isComplete = newLength >= fullContent.length;

  return { displayContent, isComplete, newLength };
}; 




