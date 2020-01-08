package seetaDeviceManager

import "testing"

func TestSetDefault(t *testing.T) {
	target := DeviceParam{}

	SetDefault(&target)

	t.Log(target)
}
