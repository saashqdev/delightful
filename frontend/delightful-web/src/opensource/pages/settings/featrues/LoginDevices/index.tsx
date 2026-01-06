import { useUserDevices } from "@/opensource/models/user/hooks"
import DelightfulSpin from "@/opensource/components/base/DelightfulSpin"
import LoginDevicesTip from "../../components/LoginDevicesTip"
import DeviceItem from "../DeviceItem"

function LoginDevices() {
	const { data: devices, isLoading } = useUserDevices()

	return (
		<>
			<LoginDevicesTip />
			<DelightfulSpin spinning={isLoading}>
				{devices?.map((item) => {
					return (
						<DeviceItem
							key={item.device_id}
							deviceId={item.device_id}
							name={item.device_name}
							system={item.os ? `${item.os} ${item.os_version}` : undefined}
							time={item.updated_at}
						/>
					)
				})}
			</DelightfulSpin>
		</>
	)
}

export default LoginDevices
