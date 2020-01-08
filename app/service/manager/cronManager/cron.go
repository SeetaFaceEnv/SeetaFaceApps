package cronManager

import (
	"SeetaDeviceCommunity/constants"
	"SeetaDeviceCommunity/utils"
	"fmt"
	"github.com/robfig/cron/v3"
)

var (
	c *cron.Cron

	nameFuncMap = map[string]func(){
		"ClearLogs": clearLogs,
	}
)

const timing = "@midnight"

func init() {
	utils.PrintLine("INIT TIMING TASK")
	fmt.Println("task cycle: ", timing)
	c = cron.New(cron.WithLocation(constants.DefaultLoc))

	fmt.Println("loading timing task:")
	for name, f := range nameFuncMap {
		fmt.Println("+", name)

		tmpF := f
		_, err := c.AddFunc(timing, tmpF)
		if err != nil {
			fmt.Println("-", name)
		}
	}

	Run()
}

func Run() {
	fmt.Println("timing task running")
	c.Start()
}
