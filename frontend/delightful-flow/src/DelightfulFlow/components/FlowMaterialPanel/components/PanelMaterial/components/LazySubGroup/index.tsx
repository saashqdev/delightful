import { MaterialGroup } from "@/DelightfulFlow/context/MaterialSourceContext/MaterialSourceContext"
import { BaseNodeType, NodeGroup, NodeWidget } from "@/DelightfulFlow/register/node"
import React, { ReactNode, useState, useRef, useEffect, memo } from "react"
import SubGroup from "../SubGroup/SubGroup"

interface LazySubGroupProps {
    subGroup: NodeGroup | MaterialGroup
    getGroupNodeList: (nodeTypes: BaseNodeType[]) => NodeWidget[]
    materialFn: (n: NodeWidget, extraProps: Record<string, any>) => ReactNode
    index: number
}

/**
 * 懒loadSubGroupcomponent，onlywhencomponent进入视口时才renderinside容
 */
function LazySubGroup({ subGroup, getGroupNodeList, materialFn, index }: LazySubGroupProps) {
    const [isVisible, setIsVisible] = useState(false);
    const [isLoaded, setIsLoaded] = useState(false);
    const ref = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const observer = new IntersectionObserver(
            ([entry]) => {
                //  Whencomponent进入视口时
                if (entry.isIntersecting) {
                    setIsVisible(true);
                    
                    //  标记foralreadyload，避免re复render
                    if (!isLoaded) {
                        setIsLoaded(true);
                    }
                    
                    //  componentalreadyload后可以解除观察
                    if (ref.current) {
                        observer.unobserve(ref.current);
                    }
                }
            },
            {
                rootMargin: '100px', //  提前100pxstartload
                threshold: 0.1 //  10%可见时触发
            }
        );

        if (ref.current) {
            observer.observe(ref.current);
        }

        return () => {
            if (ref.current) {
                observer.unobserve(ref.current);
            }
        };
    }, [isLoaded]);

    //  RenderCHSitem占位符或实际inside容
    return (
        <div ref={ref} style={{ minHeight: '50px' }}>
            {(isVisible || isLoaded) ? (
                <SubGroup
                    subGroup={subGroup}
                    getGroupNodeList={getGroupNodeList}
                    materialFn={(n, extraProps) => materialFn(n, {
                        ...extraProps,
                        key: `item-${index}-${n.schema?.id || 0}`
                    })}
                />
            ) : (
                <div style={{ height: '50px', display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                    loadin...
                </div>
            )}
        </div>
    );
}

export default memo(LazySubGroup); 
