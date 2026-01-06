/**
 * @description Wait for a condition; resolve true when satisfied, false on timeout
 * @case 
    waitFor({
        dataFn:()=>{return window.test}, 
        timeout: 10000,
        intervel: 1000
    }).then((res)=>{
        console.log(res)
    })
 */
interface WaitForType {
    dataFn?:Function,
    timeout?:number,
    intervel?:number
}
export const waitFor: (arg0: WaitForType)=>Promise<boolean> = async({dataFn, timeout, intervel} = {
     timeout: 5000, intervel: 1000
})=>{
    return new Promise( (resolve)=>{
        const intervalId = setInterval(()=>{
            if(dataFn && dataFn()){
                resolve(true)
            }
        }, intervel)
        setTimeout(()=>{
            resolve(false)
            clearInterval(intervalId)
        }, timeout)
       
    })
} 