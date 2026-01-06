/**
 * Template string parser that supports ${expression} interpolation.
 *
 * @param template Template string containing ${expression} placeholders
 * @param context Context object used when resolving expressions
 * @param options Configuration options
 * @param options.partial Keep the original expression text when parsing fails; defaults to false and returns undefined
 * @returns Resolved string
 */
export function resolveToString(
  template: string,
  context: Record<string, any> = {},
  options: { partial?: boolean } = {}
): string {
  // Handle escape sequences
  let result = '';
  let i = 0;
  const length = template.length;
  
  while (i < length) {
    // Handle escape sequences
    if (template[i] === '\\' && i + 1 < length && template[i + 1] === '$') {
      result += '$';
      i += 2; // Skip \$
      continue;
    }
    
    // Find the start of the next template expression
    if (template[i] === '$' && i + 1 < length && template[i + 1] === '{') {
      // Locate the end of the expression
      const start = i;
      i += 2; // Skip ${
      let braceCount = 1;
      let expressionEnd = -1;
      
      while (i < length) {
        if (template[i] === '{') {
          braceCount++;
        } else if (template[i] === '}') {
          braceCount--;
          if (braceCount === 0) {
            expressionEnd = i;
            break;
          }
        }
        i++;
      }
      
      if (expressionEnd === -1) {
        // No matching brace found; treat the rest as plain text
        result += template.substring(start);
        i = length;
      } else {
        // Extract expression
        const expression = template.substring(start + 2, expressionEnd);
        i = expressionEnd + 1; // Skip }
        
        try {
          // Gather valid variable names from the context
          const names = Object.keys(context).filter(name => /^[a-zA-Z_$][a-zA-Z0-9_$]*$/.test(name));
          const argNames = names.join(', ');
          const argValues = names.map(name => context[name]);

          // Use Function constructor to evaluate the expression
          // eslint-disable-next-line no-new-func
          const resolver = new Function(argNames, `return (${expression})`);
          
          try {
            // Execute expression and capture result
            const exprResult = resolver.apply(null, argValues);
            result += exprResult === undefined ? '' : String(exprResult);
          } catch (e) {
            // Expression execution failed
            if (options.partial) {
              result += '${' + expression + '}'; // Return original template text ${...}
            } else {
              result += 'undefined';
            }
          }
        } catch (e) {
          // Expression compilation failed
          if (options.partial) {
            result += '${' + expression + '}'; // Return original template text ${...}
          } else {
            result += 'undefined';
          }
        }
      }
    } else {
      // Regular character
      result += template[i];
      i++;
    }
  }
  
  return result;
}

export default resolveToString; 
