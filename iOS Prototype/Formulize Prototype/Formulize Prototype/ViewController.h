//
//  ViewController.h
//  Formulize Prototype
//
//  Created by Mary Nelly on 10-11-13.
//  Copyright (c) 2013 Laurentian University. All rights reserved.
//

#import <UIKit/UIKit.h>

@interface ViewController : UIViewController <UITableViewDataSource>
{
    NSArray *applicationData;
}

@property (weak, nonatomic) IBOutlet UIButton *signinButton;

@property (nonatomic, retain) NSArray *applicationData;

@end
